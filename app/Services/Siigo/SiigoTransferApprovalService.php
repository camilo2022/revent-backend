<?php

namespace App\Services\Siigo;

use App\Jobs\ImportMasiveTransferSiigoJob;
use App\Services\SiigoTransferNTPdfService;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Consultas (solo lectura) para aprobar una nota de traslado hacia una bodega de tránsito:
 * lee la NT, deduce la bodega final y consulta lo disponible hoy en la bodega de tránsito.
 * La creación del traslado la hace ImportMasiveTransferSiigoJob, igual que la carga por Excel.
 */
class SiigoTransferApprovalService
{
    private const TRANSFER_NOTE = 23;
    private const PATH_TRANSFER_VIEW = '/ACEntryApi/api/v1/WarehouseTransfer/GetDataView';
    private const SEARCH_PAGES = 3;
    private const ACCEPT_ANY = '*/*';

    public function __construct(private SiigoClient $client, private SiigoTransferNTPdfService $notes)
    {
    }

    /** Bodegas configuradas en el job de traslados masivos (DIRECTO y TRANSITO), leídas sin modificarlo. */
    public function warehouses(): array
    {
        return (new \ReflectionClassConstant(ImportMasiveTransferSiigoJob::class, 'BODEGAS'))->getValue();
    }

    /**
     * @return array{note: ?array, error: ?string}
     *         note = code, id, date, origin, transit, destination, items[code, description, reference, color, category, size, requested]
     */
    public function find(string $query): array
    {
        $token = $this->client->token();

        $document = $this->notes->find_document($token, trim($query), self::TRANSFER_NOTE);

        if (!$document) {
            return $this->fail('No se encontró la nota de traslado en Siigo.');
        }

        $view = $this->client->services($token)
            ->replaceHeaders(['Accept' => self::ACCEPT_ANY])
            ->asJson()
            ->get(self::PATH_TRANSFER_VIEW, ['id' => $document['ACEntryID']]);

        if (!$view->successful()) {
            return $this->fail('No fue posible leer la nota de traslado en Siigo.');
        }

        if ($view->json('IsAnnulled')) {
            return $this->fail('La nota de traslado está anulada.');
        }

        $items = $this->notes->parse_transfer_items($this->notes->transfer_note_html($token, $document['ACEntryID']));

        if (empty($items)) {
            return $this->fail('La nota de traslado no tiene productos.');
        }

        $origins = array_values(array_unique(array_column($items, 'WarehouseOrigin')));

        if (count($origins) !== 1) {
            return $this->fail('No se puede hacer la recepción: los productos de la nota no tienen la misma bodega de salida (' . implode(', ', $origins) . ').');
        }

        $destinations = array_values(array_unique(array_column($items, 'WarehouseDestination')));

        if (count($destinations) !== 1) {
            return $this->fail('No se puede hacer la recepción: los productos de la nota no van todos a la misma bodega de tránsito (' . implode(', ', $destinations) . ').');
        }

        $warehouses = $this->warehouses();
        $transitId = $this->warehouseIdByName($warehouses['TRANSITO'], $destinations[0]);

        if (!$transitId) {
            return $this->fail("No se puede hacer la recepción: la bodega de destino de la nota ({$destinations[0]}) no es una bodega de tránsito.");
        }

        $finalId = collect($warehouses['DIRECTO'])->search(fn ($bodega) => ($bodega['transito'] ?? null) == $transitId);

        if ($finalId === false) {
            return $this->fail('La bodega de tránsito no tiene una bodega final configurada.');
        }

        return [
            'note' => [
                'code' => $view->json('DocName') ?: $document['Code'],
                'id' => $document['ACEntryID'],
                'date' => $view->json('DocDate'),
                'origin' => $origins[0],
                'transit' => ['id' => $transitId, 'name' => $warehouses['TRANSITO'][$transitId]['name']],
                'destination' => ['id' => $finalId, 'name' => $warehouses['DIRECTO'][$finalId]['name']],
                'items' => $this->group_items($items),
                'images' => $this->notes->image_groups($items, true)->all(),
            ],
            'error' => null,
        ];
    }

    /**
     * Unidades disponibles hoy en la bodega para cada código (0 si no hay existencia registrada).
     * Misma consulta de existencias que usa la carga masiva de traslados.
     *
     * @param  array<int,string> $codes
     * @return array<string,int>
     */
    public function availability(string $userToken, array $codes, int $warehouseId): array
    {
        $productIds = $this->product_ids($userToken, $codes);
        $date = now()->format('Ymd');

        $responses = $this->pool($userToken, collect($productIds)->map(fn ($id) => [
            'type' => 1,
            'browserID' => '67',
            'query' => '',
            'filter' => "productcode = {$id} AND period < CONCAT(Year('$date'), FORMAT(CONVERT(datetime, '$date'), 'MM'))",
            'filter2' => "transactiondate >= CONCAT(Year('$date'), FORMAT(CONVERT(datetime, '$date'), 'MM'), '01') AND CONVERT(DATE, transactiondate, 120) <= CONVERT(DATE, '$date', 120) AND productcode = {$id}",
            'numRecordView' => 0,
            'tags' => (object) ['{FilterWareHouse}' => "{condition} ProductWarehouseId = {$warehouseId}"],
        ])->all());

        $available = [];

        foreach ($codes as $code) {
            $stock = collect($this->decode($responses[$code] ?? null))->where('WarehouseID', $warehouseId)->first();

            $available[$code] = max(0, (int) floor((float) ($stock['Value'] ?? 0)));
        }

        return $available;
    }

    /** @return array<string,int|string> código => ProductID de Siigo (solo los que existen) */
    private function product_ids(string $userToken, array $codes): array
    {
        $ids = [];
        $pending = array_values($codes);

        for ($page = 0; $page < self::SEARCH_PAGES && $pending; $page++) {
            $responses = $this->pool($userToken, collect($pending)->mapWithKeys(fn ($code) => [$code => [
                'type' => 1,
                'browserID' => '33',
                'query' => $code,
                'filter' => 'IsInventoryControl=1',
                'numRecordView' => $page * 10,
                'tags' => (object) [],
            ]])->all());

            foreach ($pending as $code) {
                $product = collect($this->decode($responses[$code] ?? null))->where('Code', $code)->first();

                if ($product) {
                    $ids[$code] = $product['ProductID'];
                }
            }

            $pending = array_values(array_diff($pending, array_keys($ids)));
        }

        return $ids;
    }

    /**
     * Autocompletar de Siigo en paralelo. Sin Accept: application/json: con esa cabecera la lista llega codificada dos veces.
     *
     * @param  array<string,array> $bodies código => cuerpo de la petición
     * @return array<string,mixed>
     */
    private function pool(string $userToken, array $bodies): array
    {
        if (empty($bodies)) {
            return [];
        }

        return Http::pool(function (Pool $pool) use ($userToken, $bodies) {
            foreach ($bodies as $code => $body) {
                $pool->as((string) $code)
                    ->baseUrl(config('siigo.urls.services'))
                    ->withToken($userToken)
                    ->withHeaders(['Accept' => self::ACCEPT_ANY])
                    ->asJson()
                    ->timeout(120)
                    ->post(config('siigo.endpoints.services.autocomplete'), $body);
            }
        });
    }

    private function decode($response): array
    {
        if (!$response instanceof Response || !$response->successful()) {
            return [];
        }

        $data = $response->json();

        return is_string($data) && $data !== '' ? (json_decode($data, true) ?: []) : [];
    }

    /** Une las líneas repetidas de un mismo código sumando la cantidad. */
    private function group_items(array $items): array
    {
        return collect($items)
            ->groupBy('ProductCode')
            ->map(function ($lines, $code) {
                $first = $lines->first();

                return [
                    'code' => $code,
                    'description' => $first['LongDescription'],
                    'reference' => $first['Reference'],
                    'color' => $first['Color'],
                    'category' => $first['Category'],
                    'size' => $first['Size'],
                    'requested' => (int) $lines->sum('Quantity'),
                ];
            })
            ->values()
            ->all();
    }

    private function warehouseIdByName(array $warehouses, string $name): int|string|null
    {
        $id = collect($warehouses)->search(fn ($bodega) => strcasecmp(trim($bodega['name']), trim($name)) === 0);

        return $id === false ? null : $id;
    }

    private function fail(string $message): array
    {
        return ['note' => null, 'error' => $message];
    }
}

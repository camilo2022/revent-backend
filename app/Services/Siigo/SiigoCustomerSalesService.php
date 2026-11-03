<?php

namespace App\Services\Siigo;

use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Clientes que compraron en un rango de fechas (y opcionalmente en un centro de costo):
 * facturas de venta de la API pública + nombre y teléfono desde el catálogo de clientes.
 */
class SiigoCustomerSalesService
{
    /** Documento del "cliente genérico" de las ventas de mostrador: no tiene nombre ni teléfono propios. */
    private const GENERIC_IDENTIFICATION = '222222222222';

    /** Los datos del cliente casi no cambian: se guardan por UUID de Siigo durante 7 días. */
    private const CACHE_PREFIX = 'SIIGO:CUSTOMER:';
    private const CACHE_SECONDS = 604800;

    /** Consultas a Siigo al mismo tiempo (más de esto dispara el límite de peticiones). */
    private const PARALLEL = 5;

    public function __construct(private SiigoClient $client)
    {
    }

    /** Centros de costo (id, código y nombre) para el filtro, ordenados por nombre. */
    public function costCenters(string $token): array
    {
        $response = $this->client->api($token)->get(config('siigo.endpoints.api.cost_centers'));

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return collect($response->json() ?? [])->sortBy('name')->values()->all();
    }

    /**
     * @return array<int,array{identification:string,name:string,phone:string,invoices:array}> un registro por cliente
     */
    public function search(string $token, string $dateStart, string $dateEnd, ?int $costCenter): array
    {
        // Siigo toma date_end como límite que no incluye: se suma un día para incluir el último
        $invoices = $this->client->paginate(config('siigo.endpoints.api.invoices'), [
            'date_start' => $dateStart,
            'date_end' => Carbon::parse($dateEnd)->addDay()->format('Y-m-d'),
        ], $token);

        $excluded = config('siigo.invoices.excluded_product_codes');
        $byCustomer = [];

        foreach ($invoices as $invoice) {
            if ($costCenter && (int) ($invoice['cost_center'] ?? 0) !== $costCenter) {
                continue;
            }

            $identification = (string) ($invoice['customer']['identification'] ?? '');
            $uuid = (string) ($invoice['customer']['id'] ?? '');

            $items = collect($invoice['items'] ?? [])
                ->whereNotIn('code', $excluded)
                ->map(fn ($item) => [
                    'code' => $item['code'] ?? '',
                    'description' => $item['description'] ?? '',
                    'quantity' => (float) ($item['quantity'] ?? 0),
                ])
                ->values()
                ->all();

            $byCustomer[$identification]['identification'] = $identification;
            $byCustomer[$identification]['uuid'] = $uuid;
            $byCustomer[$identification]['invoices'][] = [
                'name' => $invoice['name'] ?? '',
                'date' => $invoice['date'] ?? '',
                'total' => (float) ($invoice['total'] ?? 0),
                'items' => $items,
            ];
        }

        $contacts = $this->customerContacts($token, array_column($byCustomer, 'uuid', 'identification'));

        foreach ($byCustomer as $identification => &$customer) {
            $customer += $contacts[$identification];
        }

        return collect($byCustomer)->sortBy('name')->values()->all();
    }

    /**
     * Nombre y teléfono de cada cliente: primero de la caché y el resto de Siigo en tandas en paralelo.
     *
     * @param array<string,string> $uuids UUID de Siigo indexado por documento
     * @return array<string,array{name:string,phone:string}> indexado por documento
     */
    private function customerContacts(string $token, array $uuids): array
    {
        $contacts = [];
        $missing = [];

        foreach ($uuids as $identification => $uuid) {
            $identification = (string) $identification;

            if ($identification === '' || $identification === self::GENERIC_IDENTIFICATION) {
                $contacts[$identification] = ['name' => 'CLIENTE GENÉRICO (SIN DATOS)', 'phone' => ''];
            } elseif ($uuid !== '' && ($cached = Cache::get(self::CACHE_PREFIX . $uuid))) {
                $contacts[$identification] = $cached;
            } else {
                $missing[$identification] = $uuid;
            }
        }

        foreach (array_chunk($missing, self::PARALLEL, true) as $batch) {
            $responses = $this->fetchBatch($token, array_keys($batch));

            // Las que Siigo limitó o fallaron se reintentan una vez, de a una, tras una pausa
            $retry = array_filter(array_keys($batch), fn ($id) => !($responses[$id] ?? null) instanceof Response || !$responses[$id]->successful());

            if ($retry) {
                sleep(2);
                $responses = array_replace($responses, $this->fetchBatch($token, $retry));
            }

            foreach ($batch as $identification => $uuid) {
                $response = $responses[$identification] ?? null;
                $found = $response instanceof Response && $response->successful();
                $customer = $found ? ($response->json('results.0') ?? []) : [];

                $contacts[$identification] = [
                    'name' => mb_strtoupper(trim(implode(' ', $customer['name'] ?? [])) ?: 'SIN NOMBRE'),
                    'phone' => collect($customer['phones'] ?? [])->pluck('number')->filter()->implode(' / '),
                ];

                // Solo se guardan las consultas que Siigo respondió bien
                if ($found && $uuid !== '') {
                    Cache::put(self::CACHE_PREFIX . $uuid, $contacts[$identification], self::CACHE_SECONDS);
                }
            }
        }

        return $contacts;
    }

    /** Consulta varios clientes a la vez en Siigo; devuelve las respuestas indexadas por documento. */
    private function fetchBatch(string $token, array $identifications): array
    {
        return Http::pool(fn (Pool $pool) => array_map(
            fn ($identification) => $pool->as((string) $identification)
                ->baseUrl(config('siigo.urls.api'))
                ->timeout(60)
                ->withHeaders(['Authorization' => $token, 'Partner-Id' => config('siigo.partner_id')])
                ->get(config('siigo.endpoints.api.customers'), ['identification' => $identification]),
            $identifications
        ));
    }
}

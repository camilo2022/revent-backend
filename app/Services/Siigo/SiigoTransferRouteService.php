<?php

namespace App\Services\Siigo;

use App\Services\SiigoTransferNTPdfService;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Ruta de una nota de traslado (NT-): de qué bodega a qué bodega fue cada producto.
 * Sale del documento de Siigo (el mismo que se usa para el PDF de la nota de traslado).
 * Los documentos ya emitidos casi no cambian, así que se guardan en caché por una semana.
 */
class SiigoTransferRouteService
{
    private const CACHE_PREFIX = 'SIIGO:NT-ROUTE:';
    private const CACHE_DAYS = 7;
    private const POOL_SIZE = 8;

    public function __construct(
        private SiigoClient $client,
        private SiigoTransferNTPdfService $transferNotes
    ) {
    }

    /**
     * @param array<int,int|string> $voucherIds ids (ACEntryID) de las notas de traslado
     * @return array<int|string,array<string,array<int,array{origin:string,destination:string,quantity:float}>>>
     *         id de la nota => código de producto => líneas; una nota que no se pudo leer no aparece
     */
    public function routes(string $token, array $voucherIds): array
    {
        $result = [];
        $missing = [];

        foreach (array_unique($voucherIds) as $id) {
            $cached = Cache::get(self::CACHE_PREFIX . $id);

            if ($cached !== null) {
                $result[$id] = $cached;
            } else {
                $missing[] = $id;
            }
        }

        foreach (array_chunk($missing, self::POOL_SIZE) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk, $token) {
                $requests = [];

                foreach ($chunk as $id) {
                    $requests[] = $pool->as((string) $id)
                        ->withToken($token)
                        ->acceptJson()
                        ->timeout(60)
                        ->get(
                            $this->client->url('services', SiigoTransferNTPdfService::HTML_VIEW_PATH),
                            ['ACEntryID' => $id, 'OverWrite' => 'true']
                        );
                }

                return $requests;
            });

            foreach ($chunk as $id) {
                $response = $responses[(string) $id] ?? null;

                if (!$response instanceof Response || !$response->successful()) {
                    continue;
                }

                $html = $response->json('response') ?? $response->json('result.response');
                $lines = $this->lines($html);

                // Un documento sin líneas de producto no se guarda: puede ser una lectura incompleta.
                if ($lines) {
                    Cache::put(self::CACHE_PREFIX . $id, $lines, now()->addDays(self::CACHE_DAYS));
                    $result[$id] = $lines;
                }
            }
        }

        return $result;
    }

    /** Líneas del documento agrupadas por código de producto. */
    private function lines(?string $html): array
    {
        $byCode = [];

        foreach ($this->transferNotes->parse_transfer_items($html) as $item) {
            $byCode[strtoupper($item['ProductCode'])][] = [
                'origin' => $item['WarehouseOrigin'],
                'destination' => $item['WarehouseDestination'],
                'quantity' => (float) $item['Quantity'],
            ];
        }

        return $byCode;
    }
}

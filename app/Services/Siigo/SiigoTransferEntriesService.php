<?php

namespace App\Services\Siigo;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Fechas de ingreso por nota de traslado (NT-) de cada producto a cada bodega.
 *
 * Las lee del reporte de trazabilidad, que tarda mucho si se consulta todo el historial (cerca de
 * 100 s por bodega). Por eso se guardan en caché las dos fechas más antiguas por producto y bodega:
 * la primera vez se recorre todo el historial y después solo lo reciente, ya que las más antiguas
 * no cambian. El reporte de inventario solo lee esa caché (no consulta Siigo).
 */
class SiigoTransferEntriesService
{
    private const CACHE_PREFIX = 'SIIGO:TRANSFER-DATES:';
    private const TRANSFER_PREFIX = 'NT-';

    public function __construct(private SiigoTraceabilityService $traceability)
    {
    }

    /** Dos fechas más antiguas por código de producto en la bodega: código => [Y-m-d, Y-m-d]. Vacío si no hay caché. */
    public function cached(int $warehouseId): array
    {
        return Cache::get(self::CACHE_PREFIX . $warehouseId)['dates'] ?? [];
    }

    /** Fecha hasta la que está al día la caché de la bodega (null si nunca se ha calculado). */
    public function upToDate(int $warehouseId): ?string
    {
        return Cache::get(self::CACHE_PREFIX . $warehouseId)['since'] ?? null;
    }

    /**
     * Actualiza la caché de una bodega: la primera vez desde config('siigo.inventory.entries_since'),
     * después desde un día antes de la última actualización.
     *
     * @param array<int,array{id:int|string,name:string}> $warehouses bodegas de Siigo
     * @return int cantidad de productos con fechas
     */
    public function refresh(string $token, int $warehouseId, array $warehouses): int
    {
        $state = Cache::get(self::CACHE_PREFIX . $warehouseId) ?? ['since' => null, 'dates' => []];

        $start = $state['since']
            ? Carbon::parse($state['since'])->subDay()->toDateString()
            : Carbon::parse(config('siigo.inventory.entries_since'))->toDateString();

        $today = now()->toDateString();

        $movements = $this->traceability->movements(
            $token,
            $warehouseId,
            $start,
            $warehouses,
            config('siigo.inventory.entry_account_groups')
        );

        $found = [];

        foreach ($movements as $movement) {
            if ((float) ($movement['EntryQuantity'] ?? 0) <= 0 || empty($movement['ElaborationDate'])) {
                continue;
            }

            if (!str_starts_with((string) ($movement['Voucher'] ?? ''), self::TRANSFER_PREFIX)) {
                continue;
            }

            $code = (string) ($movement['ProductCode'] ?? '');

            if ($code === '') {
                continue;
            }

            $found[$code][Carbon::parse($movement['ElaborationDate'])->toDateString()] = true;
        }

        unset($movements);

        $dates = $state['dates'];

        foreach ($found as $code => $newDates) {
            $all = array_unique([...($dates[$code] ?? []), ...array_keys($newDates)]);
            sort($all);
            $dates[$code] = array_slice($all, 0, 2);
        }

        // Solo se guarda cuando todo salió bien: si Siigo falla, la caché anterior queda intacta.
        Cache::forever(self::CACHE_PREFIX . $warehouseId, ['since' => $today, 'dates' => $dates]);

        return count($dates);
    }
}

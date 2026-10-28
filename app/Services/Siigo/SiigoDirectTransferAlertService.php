<?php

namespace App\Services\Siigo;

use Carbon\Carbon;

/**
 * Traslados directos que salieron de una bodega en un día: notas de traslado (NT-) cuya bodega de destino
 * no es una bodega "TRANSITO ...". Usa los mismos movimientos y la misma lectura de ruta que la trazabilidad
 * con auditoría, pero de todos los productos de la bodega y no de una lista de códigos.
 */
class SiigoDirectTransferAlertService
{
    private const TRANSFER_PREFIX = 'NT-';
    private const TRANSIT_PREFIX = 'TRANSITO ';

    public function __construct(
        private SiigoTraceabilityService $traceability,
        private SiigoTransferRouteService $routes
    ) {
    }

    /**
     * @param array<int,array{id:int|string,name:string}> $warehouses bodegas de Siigo
     * @return array<int,array{voucher:string,url:string,date:string,time:string,origin:string,destination:string,units:float,lines:array}>
     *         una entrada por nota de traslado directa, ordenadas por fecha
     */
    public function detect(string $token, int $warehouseId, Carbon $day, array $warehouses): array
    {
        $warehouse = collect($warehouses)->first(fn ($w) => (int) $w['id'] === $warehouseId);
        $origin = $warehouse['name'] ?? (string) $warehouseId;

        $outputs = collect($this->traceability->movements($token, $warehouseId, $day->toDateString(), $warehouses))
            ->filter(fn ($m) => (float) ($m['OutputQuantity'] ?? 0) > 0
                && str_starts_with((string) ($m['Voucher'] ?? ''), self::TRANSFER_PREFIX)
                && !empty($m['VoucherID'])
                && !empty($m['ElaborationDate'])
                && Carbon::parse($m['ElaborationDate'])->isSameDay($day))
            ->values();

        if ($outputs->isEmpty()) {
            return [];
        }

        $routes = $this->routes->routes($token, $outputs->pluck('VoucherID')->unique()->all());
        $notes = [];

        foreach ($outputs as $movement) {
            $id = $movement['VoucherID'];
            $code = strtoupper((string) ($movement['ProductCode'] ?? ''));

            foreach ($routes[$id][$code] ?? [] as $line) {
                if ($this->isTransit($line['destination'])) {
                    continue;
                }

                $at = Carbon::parse($movement['ElaborationDate']);

                $notes[$id] ??= [
                    'voucher' => (string) $movement['Voucher'],
                    'url' => SiigoLinks::transferNote($id),
                    'date' => $at->format('d/m/Y'),
                    'time' => $at->format('h:i A'),
                    'at' => $at,
                    'origin' => $origin,
                    'destinations' => [],
                    'units' => 0.0,
                    'lines' => [],
                ];

                $notes[$id]['destinations'][$line['destination']] = true;
                $notes[$id]['units'] += $line['quantity'];
                $notes[$id]['lines'][] = [
                    'code' => $code,
                    'name' => $movement['ProductName'] ?? null,
                    'reference' => $movement['FactoryReference'] ?? null,
                    'destination' => $line['destination'],
                    'quantity' => $line['quantity'],
                ];
            }
        }

        return collect($notes)
            ->sortBy('at')
            ->map(fn ($note) => [
                ...collect($note)->except(['at', 'destinations'])->all(),
                'destination' => implode(', ', array_keys($note['destinations'])),
            ])
            ->values()
            ->all();
    }

    private function isTransit(string $warehouseName): bool
    {
        return str_starts_with(mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $warehouseName))), self::TRANSIT_PREFIX);
    }
}

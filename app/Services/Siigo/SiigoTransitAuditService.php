<?php

namespace App\Services\Siigo;

use Carbon\Carbon;

/**
 * Trazabilidad de productos en una bodega con auditoría de tránsitos.
 *
 * Devuelve todos los movimientos de cada código (entradas y salidas, con el saldo acumulado
 * calculado igual que el Excel de trazabilidad) y, para cada ingreso por traslado (NT-), revisa
 * si antes ya había entrado a la bodega de tránsito de esa bodega ("TRANSITO <bodega>"). Un
 * ingreso sin tránsito previo es un traslado directo.
 *
 * Los ingresos se cruzan por cantidad (primero en entrar, primero en cubrirse): un ingreso de
 * 10 unidades con solo 6 pasadas por tránsito queda "parcial" (6 por tránsito, 4 directas).
 */
class SiigoTransitAuditService
{
    public const VIA_TRANSIT = 'transito';
    public const DIRECT = 'directo';
    public const PARTIAL = 'parcial';
    public const NO_TRANSIT_WAREHOUSE = 'sin_bodega_transito';

    /** Comprobante de traslado entre bodegas. */
    private const TRANSFER_PREFIX = 'NT-';

    public function __construct(private SiigoTraceabilityService $traceability)
    {
    }

    /**
     * @param array<int,string>                           $codes      códigos de producto a revisar
     * @param array<int,array{id:int|string,name:string}> $warehouses bodegas de Siigo
     * @return array{warehouse:string,transit:?string,start_date:string,products:array<int,array>,summary:array<string,int>}
     */
    public function audit(string $token, int $warehouseId, string $startDate, array $codes, array $warehouses): array
    {
        $warehouse = collect($warehouses)->first(fn ($w) => (int) $w['id'] === $warehouseId);
        $transit = $this->transitWarehouse($warehouse['name'] ?? '', $warehouses);

        // Las bolsas (G18022025, P18022025) no son mercancía: no se auditan.
        $bags = array_map('strtoupper', config('siigo.invoices.excluded_product_codes'));
        $wanted = array_flip(array_diff(array_map('strtoupper', $codes), $bags));

        $movements = $this->movementsByProduct(
            $this->traceability->movements($token, $warehouseId, $startDate, $warehouses),
            $wanted
        );

        $transitEntries = [];

        if ($transit) {
            // Se retrocede para ver tránsitos anteriores al inicio de la consulta.
            $transitStart = Carbon::parse($startDate)
                ->subDays((int) config('siigo.inventory.transit_lookback_days'))
                ->toDateString();

            $transitEntries = $this->transitEntriesByProduct(
                $this->traceability->movements($token, (int) $transit['id'], $transitStart, $warehouses),
                $wanted
            );
        }

        $products = [];
        $summary = [self::VIA_TRANSIT => 0, self::DIRECT => 0, self::PARTIAL => 0, self::NO_TRANSIT_WAREHOUSE => 0, 'otros' => 0, 'sin_movimientos' => 0];

        foreach (array_keys($wanted) as $code) {
            $list = $movements[$code] ?? [];
            $results = $this->classify($list, $transitEntries[$code] ?? [], (bool) $transit);

            $rows = [];
            $balance = null;

            foreach ($list as $idx => $m) {
                $balance = ($balance ?? $m['initial']) + $m['entry'] - $m['output'];
                $result = $results[$idx] ?? null;

                if ($m['entry'] > 0) {
                    $key = $result['status'] ?? 'otros';
                    $summary[$key] = ($summary[$key] ?? 0) + 1;
                }

                $rows[] = [
                    'date' => $m['at']->format('d/m/Y'),
                    'time' => $m['at']->format('h:i A'),
                    'voucher' => $m['voucher'],
                    'voucher_id' => $m['voucher_id'],
                    'url' => $m['url'],
                    'sequence' => $m['sequence'],
                    'initial' => $m['initial'],
                    'entry' => $m['entry'],
                    'output' => $m['output'],
                    'balance' => $balance,
                    'entry_value' => $m['entry_value'],
                    'output_value' => $m['output_value'],
                    'status' => $result['status'] ?? null,
                    'via_transit' => $result['via_transit'] ?? 0.0,
                    'direct' => $result['direct'] ?? 0.0,
                    'transit_vouchers' => $result['transit_vouchers'] ?? [],
                ];
            }

            if (!$rows) {
                $summary['sin_movimientos']++;
            }

            $products[] = [
                'code' => $code,
                'name' => $list[0]['name'] ?? null,
                'factory_reference' => $list[0]['factory_reference'] ?? null,
                'rows' => $rows,
            ];
        }

        return [
            'warehouse' => $warehouse['name'] ?? (string) $warehouseId,
            'transit' => $transit['name'] ?? null,
            'start_date' => $startDate,
            'products' => $products,
            'summary' => $summary,
        ];
    }

    /** Bodega "TRANSITO <nombre>" de la bodega, si existe. */
    private function transitWarehouse(string $name, array $warehouses): ?array
    {
        $target = $this->normalize('TRANSITO ' . $name);

        foreach ($warehouses as $warehouse) {
            if ($this->normalize($warehouse['name']) === $target) {
                return $warehouse;
            }
        }

        return null;
    }

    private function normalize(string $text): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $text)));
    }

    /**
     * Todos los movimientos por código, en el orden en que los entrega Siigo (el mismo del Excel).
     *
     * @param array<string,int> $wanted códigos buscados (llaves)
     * @return array<string,array<int,array>>
     */
    private function movementsByProduct(array $movements, array $wanted): array
    {
        $byCode = [];

        foreach ($movements as $movement) {
            $code = strtoupper((string) ($movement['ProductCode'] ?? ''));

            if (!isset($wanted[$code])) {
                continue;
            }

            $voucher = (string) ($movement['Voucher'] ?? '');

            $byCode[$code][] = [
                'name' => $movement['ProductName'] ?? null,
                'factory_reference' => $movement['FactoryReference'] ?? null,
                'at' => Carbon::parse($movement['ElaborationDate']),
                'voucher' => $voucher,
                'voucher_id' => $movement['VoucherID'] ?? null,
                'url' => $this->voucherUrl($voucher, $movement['VoucherID'] ?? null),
                'sequence' => $movement['SEQUENCE'] ?? null,
                'initial' => (float) ($movement['InitialQuantityHidden'] ?? 0),
                'entry' => (float) ($movement['EntryQuantity'] ?? 0),
                'output' => (float) ($movement['OutputQuantity'] ?? 0),
                'entry_value' => $movement['EntryValue'] ?? null,
                'output_value' => $movement['OutputValue'] ?? null,
            ];
        }

        return $byCode;
    }

    /**
     * Ingresos a la bodega de tránsito por código, ordenados por fecha.
     *
     * @param array<string,int> $wanted códigos buscados (llaves)
     * @return array<string,array<int,array>>
     */
    private function transitEntriesByProduct(array $movements, array $wanted): array
    {
        $byCode = [];

        foreach ($movements as $movement) {
            $code = strtoupper((string) ($movement['ProductCode'] ?? ''));

            if (!isset($wanted[$code]) || (float) ($movement['EntryQuantity'] ?? 0) <= 0) {
                continue;
            }

            $voucher = (string) ($movement['Voucher'] ?? '');

            $byCode[$code][] = [
                'at' => Carbon::parse($movement['ElaborationDate']),
                'voucher' => $voucher,
                'url' => $this->voucherUrl($voucher, $movement['VoucherID'] ?? null),
                'quantity' => (float) $movement['EntryQuantity'],
            ];
        }

        foreach ($byCode as &$list) {
            usort($list, fn ($a, $b) => $a['at'] <=> $b['at']);
        }

        return $byCode;
    }

    /**
     * Cruza los ingresos por traslado a la bodega con los ingresos previos a su tránsito.
     *
     * @param array<int,array> $movements movimientos del código en la bodega (con su posición como llave)
     * @return array<int,array> posición del movimiento => estado, cantidades y comprobantes de tránsito
     */
    private function classify(array $movements, array $transitEntries, bool $hasTransit): array
    {
        $available = array_map(fn ($t) => $t + ['left' => $t['quantity']], $transitEntries);

        $transfers = [];

        foreach ($movements as $idx => $m) {
            if ($m['entry'] > 0 && str_starts_with($m['voucher'], self::TRANSFER_PREFIX)) {
                $transfers[$idx] = $m;
            }
        }

        // Los ingresos más antiguos se cubren primero.
        uasort($transfers, fn ($a, $b) => $a['at'] <=> $b['at']);

        $results = [];

        foreach ($transfers as $idx => $entry) {
            if (!$hasTransit) {
                $results[$idx] = ['status' => self::NO_TRANSIT_WAREHOUSE, 'via_transit' => 0.0, 'direct' => $entry['entry'], 'transit_vouchers' => []];
                continue;
            }

            $pending = $entry['entry'];
            $viaTransit = 0.0;
            $used = [];

            foreach ($available as &$transitEntry) {
                if ($pending <= 0) {
                    break;
                }

                if ($transitEntry['left'] <= 0 || $transitEntry['at'] > $entry['at']) {
                    continue;
                }

                $take = min($pending, $transitEntry['left']);
                $transitEntry['left'] -= $take;
                $pending -= $take;
                $viaTransit += $take;
                $used[] = [
                    'voucher' => $transitEntry['voucher'],
                    'url' => $transitEntry['url'],
                    'date' => $transitEntry['at']->format('d/m/Y'),
                    'quantity' => $take,
                ];
            }
            unset($transitEntry);

            $direct = max(0.0, $pending);

            $results[$idx] = [
                'status' => $direct <= 0 ? self::VIA_TRANSIT : ($viaTransit > 0 ? self::PARTIAL : self::DIRECT),
                'via_transit' => $viaTransit,
                'direct' => $direct,
                'transit_vouchers' => $used,
            ];
        }

        return $results;
    }

    private function voucherUrl(string $voucher, int|string|null $id): string
    {
        if ($id === null || $voucher === '') {
            return '';
        }

        return match (true) {
            str_starts_with($voucher, 'FV-') => SiigoLinks::salesInvoice($id),
            str_starts_with($voucher, 'FC-') => SiigoLinks::purchaseInvoice($id),
            str_starts_with($voucher, 'NT-') => SiigoLinks::transferNote($id),
            str_starts_with($voucher, 'A-') => SiigoLinks::inventoryEntry($id),
            str_starts_with($voucher, 'NC-') => SiigoLinks::creditNote($id),
            default => '',
        };
    }
}

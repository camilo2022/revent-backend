<?php

namespace App\Services\Siigo;

use Carbon\Carbon;

/**
 * Auditoría de traslados: para cada ingreso por traslado (NT-) de una referencia a una bodega
 * revisa si antes ya había entrado a la bodega de tránsito de esa bodega ("TRANSITO <bodega>").
 * Un ingreso sin tránsito previo es un traslado directo.
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
     * @param array<int,string>                              $codes      códigos de producto a revisar
     * @param array<int,array{id:int|string,name:string}>    $warehouses bodegas de Siigo
     * @return array{warehouse:string,transit:?string,start_date:string,products:array<int,array>,summary:array<string,int>}
     */
    public function audit(string $token, int $warehouseId, string $startDate, array $codes, array $warehouses): array
    {
        $warehouse = collect($warehouses)->first(fn ($w) => (int) $w['id'] === $warehouseId);
        $transit = $this->transitWarehouse($warehouse['name'] ?? '', $warehouses);

        // Las bolsas (G18022025, P18022025) no son mercancía: no se auditan.
        $bags = array_map('strtoupper', config('siigo.invoices.excluded_product_codes'));
        $wanted = array_flip(array_diff(array_map('strtoupper', $codes), $bags));

        $entries = $this->entriesByProduct(
            $this->traceability->movements($token, $warehouseId, $startDate, $warehouses),
            $wanted
        );

        $transitEntries = [];

        if ($transit) {
            // Se retrocede para ver tránsitos anteriores al inicio de la consulta.
            $transitStart = Carbon::parse($startDate)
                ->subDays((int) config('siigo.inventory.transit_lookback_days'))
                ->toDateString();

            $transitEntries = $this->entriesByProduct(
                $this->traceability->movements($token, (int) $transit['id'], $transitStart, $warehouses),
                $wanted
            );
        }

        $products = [];
        $summary = [self::VIA_TRANSIT => 0, self::DIRECT => 0, self::PARTIAL => 0, 'otros' => 0, 'sin_movimientos' => 0];

        foreach (array_keys($wanted) as $code) {
            $rows = $this->classify($entries[$code] ?? [], $transitEntries[$code] ?? [], (bool) $transit);

            foreach ($rows as $row) {
                $summary[$row['status'] ?? 'otros'] = ($summary[$row['status'] ?? 'otros'] ?? 0) + 1;
            }

            if (!$rows) {
                $summary['sin_movimientos']++;
            }

            $first = $entries[$code][0] ?? null;

            $products[] = [
                'code' => $code,
                'name' => $first['name'] ?? null,
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
     * Ingresos (cantidad de entrada) por código de producto, ordenados por fecha.
     *
     * @param array<string,int> $wanted códigos buscados (llaves)
     * @return array<string,array<int,array>>
     */
    private function entriesByProduct(array $movements, array $wanted): array
    {
        $byCode = [];

        foreach ($movements as $movement) {
            $code = strtoupper((string) ($movement['ProductCode'] ?? ''));

            if (!isset($wanted[$code]) || (float) ($movement['EntryQuantity'] ?? 0) <= 0) {
                continue;
            }

            $voucher = (string) ($movement['Voucher'] ?? '');

            $byCode[$code][] = [
                'name' => $movement['ProductName'] ?? null,
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
     * Cruza los ingresos a la bodega con los ingresos previos a su tránsito.
     *
     * @return array<int,array>
     */
    private function classify(array $entries, array $transitEntries, bool $hasTransit): array
    {
        $available = array_map(fn ($t) => $t + ['left' => $t['quantity']], $transitEntries);
        $rows = [];

        foreach ($entries as $entry) {
            $row = [
                'date' => $entry['at']->format('d/m/Y'),
                'time' => $entry['at']->format('h:i A'),
                'voucher' => $entry['voucher'],
                'url' => $entry['url'],
                'quantity' => $entry['quantity'],
                'status' => null,
                'via_transit' => 0.0,
                'direct' => 0.0,
                'transit_vouchers' => [],
            ];

            if (!str_starts_with($entry['voucher'], self::TRANSFER_PREFIX)) {
                $rows[] = $row; // compra, ajuste o devolución: no es un traslado
                continue;
            }

            if (!$hasTransit) {
                $row['status'] = self::NO_TRANSIT_WAREHOUSE;
                $row['direct'] = $entry['quantity'];
                $rows[] = $row;
                continue;
            }

            $pending = $entry['quantity'];

            foreach ($available as &$transitEntry) {
                if ($pending <= 0) {
                    break;
                }

                if ($transitEntry['left'] <= 0 || $transitEntry['at'] > $entry['at']) {
                    continue;
                }

                $used = min($pending, $transitEntry['left']);
                $transitEntry['left'] -= $used;
                $pending -= $used;
                $row['via_transit'] += $used;
                $row['transit_vouchers'][] = [
                    'voucher' => $transitEntry['voucher'],
                    'url' => $transitEntry['url'],
                    'date' => $transitEntry['at']->format('d/m/Y'),
                    'quantity' => $used,
                ];
            }
            unset($transitEntry);

            $row['direct'] = max(0.0, $pending);
            $row['status'] = $row['direct'] <= 0
                ? self::VIA_TRANSIT
                : ($row['via_transit'] > 0 ? self::PARTIAL : self::DIRECT);

            $rows[] = $row;
        }

        return $rows;
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

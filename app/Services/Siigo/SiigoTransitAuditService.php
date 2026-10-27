<?php

namespace App\Services\Siigo;

use Carbon\Carbon;

/**
 * Trazabilidad de productos en una bodega con auditoría de traslados.
 *
 * Devuelve todos los movimientos de cada código (entradas y salidas, con el saldo acumulado
 * calculado igual que el Excel de trazabilidad) y, para cada nota de traslado (NT-), lee de qué
 * bodega a qué bodega fue (del documento de Siigo):
 *
 *  - Ingreso cuyo origen es "TRANSITO <esta bodega>"      -> por tránsito (correcto).
 *  - Ingreso cuyo origen es cualquier otra bodega         -> traslado directo (se muestra el origen).
 *  - Salida cuyo destino es una bodega "TRANSITO ..."     -> sale a tránsito (correcto).
 *  - Salida cuyo destino es cualquier otra bodega         -> salida directa a otra bodega (se resalta).
 */
class SiigoTransitAuditService
{
    public const VIA_TRANSIT = 'transito';
    public const DIRECT = 'directo';
    public const PARTIAL = 'parcial';
    public const NO_TRANSIT_WAREHOUSE = 'sin_bodega_transito';
    public const EXIT_TO_TRANSIT = 'salida_transito';
    public const EXIT_DIRECT = 'salida_directa';
    public const NO_ROUTE = 'sin_origen';

    /** Comprobante de traslado entre bodegas. */
    private const TRANSFER_PREFIX = 'NT-';
    private const TRANSIT_PREFIX = 'TRANSITO ';

    public function __construct(
        private SiigoTraceabilityService $traceability,
        private SiigoTransferRouteService $routes
    ) {
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

        $routes = $this->routes->routes($token, $this->transferIds($movements));

        $products = [];
        $summary = [
            self::VIA_TRANSIT => 0, self::DIRECT => 0, self::PARTIAL => 0, self::NO_TRANSIT_WAREHOUSE => 0,
            self::EXIT_TO_TRANSIT => 0, self::EXIT_DIRECT => 0, self::NO_ROUTE => 0, 'otros' => 0, 'sin_movimientos' => 0,
        ];

        foreach (array_keys($wanted) as $code) {
            $list = $movements[$code] ?? [];

            $rows = [];
            $balance = null;

            foreach ($list as $m) {
                $balance = ($balance ?? $m['initial']) + $m['entry'] - $m['output'];

                $result = str_starts_with($m['voucher'], self::TRANSFER_PREFIX)
                    ? $this->classify($m, $routes[$m['voucher_id']][$code] ?? null, $warehouse['name'] ?? '', (bool) $transit)
                    : null;

                if ($result) {
                    $summary[$result['status']]++;
                } elseif ($m['entry'] > 0) {
                    $summary['otros']++;
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
                    'origin' => $result['origin'] ?? null,
                    'destination' => $result['destination'] ?? null,
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

    /**
     * Estado de una nota de traslado según su ruta (origen y destino de cada línea del producto).
     *
     * @param array<int,array{origin:string,destination:string,quantity:float}>|null $lines null si no se pudo leer el documento
     */
    private function classify(array $m, ?array $lines, string $warehouseName, bool $hasTransit): ?array
    {
        if ($m['entry'] <= 0 && $m['output'] <= 0) {
            return null;
        }

        if ($lines === null) {
            return ['status' => self::NO_ROUTE, 'via_transit' => 0.0, 'direct' => 0.0, 'origin' => null, 'destination' => null];
        }

        if ($m['entry'] > 0) {
            $viaTransit = 0.0;
            $direct = 0.0;
            $origins = [];

            foreach ($lines as $line) {
                $this->isTransit($line['origin']) ? $viaTransit += $line['quantity'] : $direct += $line['quantity'];
                $origins[$line['origin']] = true;
            }

            $status = !$hasTransit
                ? self::NO_TRANSIT_WAREHOUSE
                : ($direct <= 0 ? self::VIA_TRANSIT : ($viaTransit > 0 ? self::PARTIAL : self::DIRECT));

            return [
                'status' => $status,
                'via_transit' => $viaTransit,
                'direct' => $direct,
                'origin' => implode(', ', array_keys($origins)),
                'destination' => $warehouseName,
            ];
        }

        $directDestinations = [];
        $destinations = [];

        foreach ($lines as $line) {
            $destinations[$line['destination']] = true;

            if (!$this->isTransit($line['destination'])) {
                $directDestinations[$line['destination']] = true;
            }
        }

        return [
            'status' => $directDestinations ? self::EXIT_DIRECT : self::EXIT_TO_TRANSIT,
            'via_transit' => 0.0,
            'direct' => 0.0,
            'origin' => $warehouseName,
            'destination' => implode(', ', array_keys($directDestinations ?: $destinations)),
        ];
    }

    private function isTransit(string $warehouseName): bool
    {
        return str_starts_with($this->normalize($warehouseName), self::TRANSIT_PREFIX);
    }

    /** Bodega "TRANSITO <nombre>" de la bodega, si existe. */
    private function transitWarehouse(string $name, array $warehouses): ?array
    {
        $target = $this->normalize(self::TRANSIT_PREFIX . $name);

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

    /** Ids de las notas de traslado que aparecen en los movimientos. */
    private function transferIds(array $movementsByCode): array
    {
        $ids = [];

        foreach ($movementsByCode as $list) {
            foreach ($list as $m) {
                if ($m['voucher_id'] !== null && str_starts_with($m['voucher'], self::TRANSFER_PREFIX)) {
                    $ids[] = $m['voucher_id'];
                }
            }
        }

        return array_values(array_unique($ids));
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

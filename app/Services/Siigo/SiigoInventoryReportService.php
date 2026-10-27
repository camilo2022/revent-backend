<?php

namespace App\Services\Siigo;

use Carbon\Carbon;

/**
 * Inventario por bodega (reporte 5443 de Siigo). Una sola llamada devuelve todas las
 * existencias de la bodega, sin recorrer páginas. Lo usan la consulta de inventario por
 * tienda y el reporte de inventarios, para que ambos lean exactamente lo mismo.
 */
class SiigoInventoryReportService
{
    private const RETRY_ATTEMPTS = 3;
    private const RETRY_WAIT_SECONDS = 10;

    public function __construct(
        private SiigoReportService $reports,
        private SiigoTraceabilityService $traceability
    ) {
    }

    /** Cuerpo del reporte de existencias "con saldo" de una bodega a la fecha de hoy. */
    public function stockBody(int|string $warehouseId): array
    {
        $filters = [
            [
                'Field'        => '_vProduct',
                'FilterType'   => 6,
                'OperatorType' => 0,
                'Value'        => [],
                'ValueUI'      => '',
                'Source'       => '2',
            ],
            [
                'Field'        => 'WarehouseFilter',
                'FilterType'   => 2,
                'OperatorType' => 0,
                'Value'        => [$warehouseId],
                'ValueUI'      => '',
                'Source'       => '',
            ],
            [
                'Field'        => '_vCutoffDate',
                'FilterType'   => 5,
                'OperatorType' => 0,
                'Value'        => [now()->toISOString()],
                'ValueUI'      => now()->format('n/j/Y'),
                'Source'       => 'Account',
            ],
            [
                'Field'        => 'ProductBalanceFilter',
                'FilterType'   => 7,
                'OperatorType' => 0,
                'Value'        => ['0'],
                'ValueUI'      => 'Con saldo',
                'Source'       => 'ProductBalancesEnum',
            ],
            [
                'Field'        => 'product',
                'FilterType'   => 2,
                'OperatorType' => 0,
                'Value'        => [-1],
                'ValueUI'      => '',
                'Source'       => '',
            ],
        ];

        return [
            'FilterCriterias'    => json_encode($filters, JSON_UNESCAPED_UNICODE),
            'GetTotalCount'      => false,
            'GridOrderCriteria'  => null,
            'Id'                 => 5443,
            'Params'             => json_encode(['TabID' => '1617', 'pTabID' => '1445', 'rReport' => '1']),
            'Skip'               => 0,
            'Sort'               => ' ',
            'Take'               => 0,
        ];
    }

    /** Existencias de una bodega: una fila por producto (QuantityBalance, Description, productcode...). */
    public function stock(string $token, int|string $warehouseId): array
    {
        return $this->withRetry(
            fn () => $this->reports->table($this->stockBody($warehouseId), 'Error consultando inventario: ', $token) ?? []
        );
    }

    /**
     * Las dos fechas más recientes en que cada referencia ingresó a la bodega
     * (movimientos con cantidad de entrada), de los últimos
     * siigo.inventory.entries_lookback_days días.
     *
     * @param array<int,array{id:int|string,name:string}> $warehouses bodegas de Siigo
     * @return array<string,array{0:string,1:?string}> referencia => [fecha más reciente, segunda fecha]
     */
    public function entryDates(string $token, int $warehouseId, array $warehouses): array
    {
        $start = Carbon::now()->subDays((int) config('siigo.inventory.entries_lookback_days'))->toDateString();

        $movements = $this->withRetry(fn () => $this->traceability->movements(
            $token,
            $warehouseId,
            $start,
            $warehouses,
            config('siigo.inventory.entry_account_groups')
        ));

        $datesByReference = [];

        foreach ($movements as $movement) {
            if ((float) ($movement['EntryQuantity'] ?? 0) <= 0 || empty($movement['ElaborationDate'])) {
                continue;
            }

            $reference = self::reference((string) ($movement['ProductName'] ?? ''));

            if ($reference === '') {
                continue;
            }

            $datesByReference[$reference][Carbon::parse($movement['ElaborationDate'])->toDateString()] = true;
        }

        unset($movements);

        $result = [];

        foreach ($datesByReference as $reference => $dates) {
            $dates = array_keys($dates);
            rsort($dates);

            $result[$reference] = [$dates[0], $dates[1] ?? null];
        }

        return $result;
    }

    /** Referencia de un producto: lo que va antes del primer "-" o "*" de su nombre, en mayúsculas. */
    public static function reference(string $productName): string
    {
        $parts = preg_split('/[*-]/', trim($productName));

        return mb_strtoupper(trim($parts[0] ?? ''));
    }

    private function withRetry(callable $call): array
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $call();
            } catch (\Throwable $e) {
                if ($attempt >= self::RETRY_ATTEMPTS) {
                    throw $e;
                }

                sleep(self::RETRY_WAIT_SECONDS);
            }
        }
    }
}

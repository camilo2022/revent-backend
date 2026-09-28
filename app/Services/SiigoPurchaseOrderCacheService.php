<?php
// app/Services/SiigoPurchaseOrderCacheService.php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta el reporte de Siigo (órdenes de compra / facturas de compra)
 * y cachea:
 *  - El listado de cada día (SIIGO-FC-DAY-{Y-m-d} / SIIGO-OC-DAY-{Y-m-d})
 *  - El detalle de cada documento ("FC-ID-{id}" / "OC-ID-{id}")
 *
 * Se usa tanto desde el controlador (lectura) como desde el Job (precalentado).
 */
class SiigoPurchaseOrderCacheService
{
    /**
     * Órdenes de compra (tipo transacción 4) entre dos fechas, directo a Siigo.
     */
    public function purchaseOrders(string $token, Carbon $fecha_inicio, Carbon $fecha_fin): Collection
    {
        return $this->report($token, '4', 'Orden de compra', $fecha_inicio, $fecha_fin);
    }

    /**
     * Facturas de compra (tipo transacción 0) entre dos fechas, directo a Siigo.
     */
    public function purchaseInvoices(string $token, Carbon $fecha_inicio, Carbon $fecha_fin): Collection
    {
        return $this->report($token, '0', 'Compra', $fecha_inicio, $fecha_fin);
    }

    /**
     * Facturas de compra leyendo del cache del listado por día.
     * Si algún día no está cacheado, lo consulta y lo guarda.
     */
    public function purchaseInvoicesCached(string $token, Carbon $from, Carbon $to): Collection
    {
        return $this->cachedRange($token, 'FC', $from, $to);
    }

    /**
     * Órdenes de compra leyendo del cache del listado por día.
     * Si algún día no está cacheado, lo consulta y lo guarda.
     */
    public function purchaseOrdersCached(string $token, Carbon $from, Carbon $to): Collection
    {
        return $this->cachedRange($token, 'OC', $from, $to);
    }

    public static function dayKey(string $type, string $day): string
    {
        return "SIIGO-{$type}-DAY-{$day}"; // FC = facturas, OC = órdenes
    }

    /**
     * Consulta un solo día a Siigo y SOBRESCRIBE el listado cacheado de ese día.
     * Si Siigo falla, lanza excepción y el cache anterior queda intacto.
     */
    public function refreshDay(string $token, string $type, string $day): array
    {
        $d = Carbon::parse($day);

        $rows = $type === 'FC'
            ? $this->purchaseInvoices($token, $d->copy()->startOfDay(), $d->copy()->endOfDay())
            : $this->purchaseOrders($token, $d->copy()->startOfDay(), $d->copy()->endOfDay());

        $rows = $rows->values()->all();

        // También guarda [] si ese día no hubo documentos
        Cache::forever(self::dayKey($type, $day), $rows);

        return $rows;
    }

    /**
     * Precalienta un rango de fechas: por cada día refresca el listado
     * (sobrescribiendo) y luego refresca el detalle de cada documento.
     *
     * Devuelve un resumen para loguear.
     */
    public function warmRange(string $token, Carbon $fecha_inicio, Carbon $fecha_fin): array
    {
        $invoices = collect();
        $orders = collect();

        foreach (CarbonPeriod::create($fecha_inicio->copy()->startOfDay(), $fecha_fin->copy()->startOfDay()) as $day) {
            $day = $day->toDateString();

            $invoices = $invoices->merge($this->refreshDay($token, 'FC', $day));
            $orders = $orders->merge($this->refreshDay($token, 'OC', $day));
        }

        $invoiceStats = $this->warmDetails($token, $invoices->pluck('ACEntryID'), 'FC-ID');
        $orderStats = $this->warmDetails($token, $orders->pluck('ACEntryID'), 'OC-ID');

        return [
            'range' => [$fecha_inicio->toDateString(), $fecha_fin->toDateString()],
            'invoices_found' => $invoices->count(),
            'invoices_cached_now' => $invoiceStats['cached_now'],
            'orders_found' => $orders->count(),
            'orders_cached_now' => $orderStats['cached_now'],
        ];
    }

    /**
     * Consulta SIEMPRE el detalle a Siigo y sobrescribe el cache existente.
     * Si falla un documento, se conserva el valor anterior.
     */
    public function warmDetails(string $token, $ids, string $prefix): array
    {
        $ids = collect($ids)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return ['total' => 0, 'cached_now' => 0];
        }

        $cachedNow = 0;

        foreach ($ids as $id) {
            $key = "{$prefix}-{$id}";

            try {
                $this->fetchDetail($token, (int) $id, $key);
                $cachedNow++;
            } catch (\Throwable $e) {
                Log::warning("[SiigoPurchaseOrderCacheService] No se pudo cachear {$key}: " . $e->getMessage());
            }
        }

        return ['total' => $ids->count(), 'cached_now' => $cachedNow];
    }

    /**
     * Consulta el detalle de un documento y lo deja cacheado con la misma
     * llave que usa el resto de la aplicación.
     */
    public function fetchDetail(string $token, int $acEntryId, string $cacheKey)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->get('https://services.siigo.com/ACEntryApi/api/v2/Purchase/GetItem', [
                'id' => $acEntryId,
            ]);

        if (!$response->successful()) {
            throw new \Exception("Error consultando detalle {$cacheKey}: " . $response->body());
        }

        $data = $response->json();

        if (empty($data)) {
            Cache::put($cacheKey, [], now()->addHour());

            return [];
        }

        Cache::forever($cacheKey, $data);

        return $data;
    }

    /**
     * Lee del cache los días del rango; si algún día no está cacheado,
     * lo consulta a Siigo y lo guarda.
     */
    private function cachedRange(string $token, string $type, Carbon $from, Carbon $to): Collection
    {
        $days = collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(fn ($d) => $d->toDateString());

        $cached = Cache::many($days->map(fn ($d) => self::dayKey($type, $d))->all());

        $rows = collect();

        foreach ($days as $day) {
            $dayRows = $cached[self::dayKey($type, $day)] ?? null;

            if ($dayRows === null) {
                $dayRows = $this->refreshDay($token, $type, $day);
            }

            $rows = $rows->merge($dayRows);
        }

        return $rows->values();
    }

    /**
     * Consulta paginada al reporte de Siigo (getreport). Solo cambian el valor
     * y la etiqueta de "_vTypeTransaction" entre órdenes y facturas.
     */
    private function report(string $token, string $typeTransactionValue, string $typeTransactionLabel, Carbon $fecha_inicio, Carbon $fecha_fin): Collection
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        $source = collect(range(2015, $fecha_fin->year))
            ->map(fn ($anio) => [
                'id' => $anio,
                'StartDate' => "{$anio}0101",
                'EndDate' => "{$anio}1231",
            ])
            ->values()
            ->toArray();

        do {
            $filterCriterias = [
                [
                    'Field' => '_vTypeTransaction',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => [$typeTransactionValue],
                    'ValueUI' => $typeTransactionLabel,
                    'Source' => 'PurchasesTransactionEnum',
                ],
                [
                    'Field' => '_vProvider',
                    'FilterType' => 68,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => 'Account',
                ],
                [
                    'Field' => '_vDocDate',
                    'FilterType' => 76,
                    'OperatorType' => 0,
                    'Value' => [
                        $fecha_inicio->format('Ymd'),
                        $fecha_fin->format('Ymd'),
                    ],
                    'ValueUI' => $fecha_inicio->format('Y/m/d') . ' - ' . $fecha_fin->format('Y/m/d'),
                    'Source' => $source,
                ],
                [
                    'Field' => '_vUser',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '12',
                ],
                [
                    'Field' => '_vProviderInvoice',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '64',
                ],
                [
                    'Field' => '_vESiigoStatus',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => ['-1'],
                    'ValueUI' => '',
                    'Source' => 'DianStateFilterEnum',
                ],
            ];

            $body = [
                'Id' => 5451,
                'Skip' => $skip,
                'Take' => $take,
                'Sort' => ' ',
                'FilterCriterias' => json_encode($filterCriterias),
                'Params' => json_encode(['TabID' => '1408']),
                'GetTotalCount' => $total === null,
                'GridOrderCriteria' => null,
                'AddOns' => [
                    [
                        'name' => 'POS Web',
                        'state' => true,
                        'tenantId' => '0x00000000000000000000000000605286',
                        'type' => 1,
                        'module' => 5,
                        'dateActive' => '09/19/2026 08:55:44.118',
                        'posActiveCashiers' => [
                            'baseCashiers' => 1,
                            'aditionalCashiers' => 28,
                        ],
                        'documentBase' => 0,
                        'readOnly' => null,
                        'subState' => 1,
                        'updateType' => 1,
                        'complements' => null,
                        'payrollComplements' => null,
                    ],
                ],
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(600)
                ->connectTimeout(30)
                ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

            if (!$response->successful()) {
                throw new \Exception("Error consultando reporte ({$typeTransactionLabel}): " . $response->body());
            }

            if ($total === null) {
                $total = (int) $response->json('totalCount');
            }

            $page = $response->json('data.Value.Table') ?? [];
            $rows = array_merge($rows, $page);
            $skip += $take;
        } while ($skip < $total);

        return collect($rows);
    }
}

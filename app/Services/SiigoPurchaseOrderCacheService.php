<?php
// app/Services/SiigoPurchaseOrderCacheService.php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extrae la parte de InvoicePurchaseOrderSiigoController que consulta
 * el reporte de Siigo (órdenes de compra / facturas de compra) y cachea
 * el detalle de cada documento (Cache::forever bajo "OC-ID-{id}" /
 * "FC-ID-{id}"), para poder reutilizarla tanto desde el controlador
 * como desde el Job de precalentado de cache.
 */
class SiigoPurchaseOrderCacheService
{
    /**
     * Órdenes de compra (tipo transacción 4) entre dos fechas.
     */
    public function purchaseOrders(string $token, Carbon $fecha_inicio, Carbon $fecha_fin): Collection
    {
        return $this->report($token, '4', 'Orden de compra', $fecha_inicio, $fecha_fin);
    }

    /**
     * Facturas de compra (tipo transacción 0) entre dos fechas.
     */
    public function purchaseInvoices(string $token, Carbon $fecha_inicio, Carbon $fecha_fin): Collection
    {
        return $this->report($token, '0', 'Compra', $fecha_inicio, $fecha_fin);
    }

    /**
     * Precalienta el cache de un rango de fechas: trae las órdenes y
     * facturas de ese rango y deja cacheado (para siempre, igual que
     * hoy) el detalle de cada una que todavía no estuviera cacheada.
     *
     * Devuelve un resumen (para loguear cuántos documentos había y
     * cuántos tuvo que consultar de verdad).
     */
    public function warmRange(string $token, Carbon $fecha_inicio, Carbon $fecha_fin): array
    {
        $invoices = $this->purchaseInvoices($token, $fecha_inicio, $fecha_fin);
        $invoiceStats = $this->warmDetails($token, $invoices->pluck('ACEntryID'), 'FC-ID');

        $orders = $this->purchaseOrders($token, $fecha_inicio, $fecha_fin);
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
     * Igual que el `details()` privado del controlador, pero
     * devolviendo cuántos tuvo que ir a consultar (los que no
     * estaban ya cacheados) en vez de la colección completa,
     * porque para el warm-up solo nos interesa dejarlos en cache.
     */
    public function warmDetails(string $token, $ids, string $prefix): array
    {
        $ids = collect($ids)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return ['total' => 0, 'cached_now' => 0];
        }

        $cached = Cache::many($ids->map(fn ($id) => "{$prefix}-{$id}")->all());
        $cachedNow = 0;

        foreach ($ids as $id) {
            $key = "{$prefix}-{$id}";

            // Ya está en cache (incluye el caso "sin datos" cacheado por 1h)
            if (array_key_exists($key, $cached) && $cached[$key] !== null) {
                continue;
            }

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
     * Idéntico al fetch_detail() privado del controlador: consulta
     * el detalle de un documento y lo deja cacheado con la misma
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
     * Consulta paginada al reporte de Siigo (getreport), igual a los
     * antiguos purchase_orders()/purchase_invoices() del controlador,
     * unificados en un solo método porque solo cambian el valor y la
     * etiqueta de "_vTypeTransaction".
     */
    private function report(string $token, string $typeTransactionValue, string $typeTransactionLabel, Carbon $fecha_inicio, Carbon $fecha_fin): Collection {
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

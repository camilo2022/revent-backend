<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\SiigoInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class InvoicePurchaseOrderSiigoController extends Controller
{
    public function invoice_purchase_order()
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $warehouses = $this->warehouses($token);

        $fecha_inicio = Carbon::now()->subDays(1);
        $fecha_fin = Carbon::now();

        $hora_inicio = Carbon::now();

        // Facturas agrupadas por la OC a la que pertenecen
        $invoices = $this->purchase_invoices($token, $fecha_inicio, $fecha_fin);
        $invoice_details = $this->details($token, $invoices->pluck('ACEntryID'), 'FC-ID');

        $purchase_invoices = $invoices
            ->map(function ($invoice) use ($invoice_details) {
                $detail = $invoice_details->get($invoice['ACEntryID']);

                $invoice['ACEntryCode'] = data_get($detail, 'Entry.ACEntryCode');
                $invoice['Items'] = $this->parse_items($detail);
                $invoice['Link'] = "https://siigonube.siigo.com/#/purchase/1008/{$invoice['ACEntryID']}";

                return $invoice;
            })
            ->groupBy('ACEntryCode');

        // Órdenes
        $orders = $this->purchase_orders($token, $fecha_inicio, $fecha_fin);
        $order_details = $this->details($token, $orders->pluck('ACEntryID'), 'OC-ID');
        $providers = collect(Cache::many(
            $orders->pluck('MsThirdPartyID')->filter()->unique()->values()->all()
        ));

        $purchase_orders = $orders->map(function ($order) use ($order_details, $providers, $purchase_invoices, $warehouses) {
            $detail = $order_details->get($order['ACEntryID']);
            $provider = $providers->get($order['MsThirdPartyID']);
            $comments = trim(data_get($provider, 'Comments', ''));
            $order_invoices = $purchase_invoices->get($order['ACEntryID'], collect());
            
            $order['Observations'] = trim(data_get($detail, 'Entry.Observations', ''));
            $order['FullName'] = strtoupper($order['FullName']);
            $order['CompanyName'] = strtoupper(data_get($provider, 'BasicData.CompanyName', ''));
            $order['TypeProvider'] = str_starts_with($comments, 'PT') ? 'PRODUCTO TERMINADO' : 'OTROS';

            preg_match('/FECHA LIMITE: (\d{4}-\d{2}-\d{2})/', $order['Observations'], $match);
            $order['DeadlineDate'] = $match[1] ?? null;

            // Items de la OC + en qué facturas se confirmaron y cuántos
            $items = collect($this->parse_items($detail))->map(function ($item) use ($order_invoices) {
                $confirmed_in = $order_invoices
                    ->map(function ($invoice) use ($item) {
                        $quantity = collect($invoice['Items'])
                            ->where('ProductCode', $item['ProductCode'])
                            ->sum('Quantity');

                        return $quantity > 0 ? [
                            'ACEntryID' => $invoice['ACEntryID'],
                            'DocName'   => $invoice['DocName'] ?? null,
                            'Quantity'  => $quantity,
                            'Link'      => $invoice['Link'],
                        ] : null;
                    })
                    ->filter()
                    ->values();

                $item['Confirmed'] = $confirmed_in->sum('Quantity');
                $item['Pending'] = $item['Quantity'] - $item['Confirmed'];
                $item['Invoices'] = $confirmed_in->all();

                return $item;
            });

            $order['Items'] = $items->all();
            $order['TotalQuantity'] = $items->sum('Quantity');
            $order['TotalConfirmed'] = $items->sum('Confirmed');
            $order['Invoices'] = $order_invoices
                ->map(fn ($invoice) => collect($invoice)->only([
                    'ACEntryID', 'DocName', 'ExternalDocumentNumber', 'DocDate', 'TotalValue', 'Link',
                ]))
                ->values();
            $order['Warehouse'] = collect($detail['Items'] ?? [])
                ->pluck('WarehouseCode')
                ->filter()
                ->unique()
                ->map(fn ($code) => $code . ' - ' . ($warehouses->get($code)['name'] ?? ''))
                ->implode(', ');
            $order['Link'] = "https://siigonube.siigo.com/#/asp/" . base64_encode("Default.aspx?TabID=1671&ERPDocumentID={$order['ACEntryID']}") . "?TabID=1671";

            return $order;
        });

        $hora_fin = Carbon::now();

        return view('integration.invoice_purchase_order', compact('purchase_orders'));
        return [$hora_inicio, $hora_fin, $purchase_orders];
    }
    
    private function parse_items(?array $detail): array
    {
        return collect($detail['Items'] ?? [])
            // Las facturas traen una línea de pago (ProductCode null, EntryItemType 3): se descarta
            ->filter(fn ($item) => !empty($item['ProductCode']))
            ->map(function ($item) {
                $parts = array_map('trim', explode('-', $item['LongDescription'] ?? ''));
                $count = count($parts);
                $valid = $count === 5;

                return [
                    'ProductCode'        => $item['ProductCode'],
                    'Description'        => $item['Description'] ?? null,
                    'LongDescription'    => $item['LongDescription'] ?? null,
                    'WarehouseCode'      => $item['WarehouseCode'] ?? null,

                    'Reference'          => $valid ? $parts[0] : null,
                    'Color'              => $valid ? $parts[1] : null,
                    'Category'           => $valid ? $parts[$count - 2] : null,
                    'Size'               => $valid ? $parts[$count - 1] : null,

                    'Quantity'           => $item['Quantity'] ?? 0,
                    'UnitValue'          => $item['UnitValue'] ?? 0,
                    'GrossValue'         => $item['GrossValue'] ?? 0,
                    'BaseValue'          => $item['BaseValue'] ?? 0,
                    'Value'              => $item['Value'] ?? 0,

                    'DiscountPercentage' => $item['DiscountPercentage'] ?? 0,
                    'DiscountValue'      => $item['DiscountValue'] ?? 0,

                    'TaxAddName'         => $item['TaxAddName'] ?? null,
                    'TaxAddPercentage'   => $item['TaxAddPercentage'] ?? 0,
                    'TaxAddValue'        => $item['TaxAddValue'] ?? 0,
                    'TaxDiscName'        => $item['TaxDiscName'] ?? null,
                    'TaxDiscPercentage'  => $item['TaxDiscPercentage'] ?? 0,
                    'TaxDiscValue'       => $item['TaxDiscValue'] ?? 0,
                ];
            })
            ->values()
            ->all();
    }

    private function purchase_orders(string $token, ?Carbon $fecha_inicio = null, ?Carbon $fecha_fin = null)
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        $fecha_inicio = $fecha_inicio ?? Carbon::now()->subMonth();
        $fecha_fin = $fecha_fin ?? Carbon::now();

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
                    'Value' => ['4'],
                    'ValueUI' => 'Orden de compra',
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
                    'ValueUI' => $fecha_inicio->format('Y/m/d')
                        . ' - '
                        . $fecha_fin->format('Y/m/d'),
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
                'Params' => json_encode([
                    'TabID' => '1408',
                ]),
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
                throw new \Exception(
                    'Error consultando órdenes de compra: ' . $response->body()
                );
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
    
    private function purchase_invoices(string $token, ?Carbon $fecha_inicio = null, ?Carbon $fecha_fin = null)
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        $fecha_inicio = $fecha_inicio ?? Carbon::now()->subMonth();
        $fecha_fin = $fecha_fin ?? Carbon::now();

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
                    'Value' => ['0'],
                    'ValueUI' => 'Compra',
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
                    'ValueUI' => $fecha_inicio->format('Y/m/d')
                        . ' - '
                        . $fecha_fin->format('Y/m/d'),
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
                'Params' => json_encode([
                    'TabID' => '1408',
                ]),
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
                throw new \Exception(
                    'Error consultando facturas de compra: ' . $response->body()
                );
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

    private function details(string $token, $ids, string $prefix)
    {
        $ids = collect($ids)->filter()->unique()->values();

        $cached = Cache::many($ids->map(fn ($id) => "{$prefix}-{$id}")->all());

        return $ids->mapWithKeys(function ($id) use ($token, $prefix, $cached) {
            $key = "{$prefix}-{$id}";

            return [$id => $cached[$key] ?? $this->fetch_detail($token, $id, $key)];
        });
    }

    private function fetch_detail(string $token, int $acEntryId, string $cacheKey)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->get('https://services.siigo.com/ACEntryApi/api/v2/Purchase/GetItem', [
                'id' => $acEntryId,
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                "Error consultando detalle {$cacheKey}: " . $response->body()
            );
        }

        $data = $response->json();

        if (empty($data)) {
            Cache::put($cacheKey, [], now()->addHour());

            return [];
        }

        Cache::forever($cacheKey, $data);

        return $data;
    }

    private function warehouses(string $token)
    {
        $response = Http::retry(5, 10000)->withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id'    => 'consultadeFacturas',
        ])->get("https://api.siigo.com/v1/warehouses");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        return collect($data)->keyBy('id')
            ->put(-1, [
                'id' => -1,
                'name' => 'SIN ASIGNAR',
            ]);
    }
}

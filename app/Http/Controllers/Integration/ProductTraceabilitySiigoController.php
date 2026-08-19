<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\SiigoInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProductTraceabilitySiigoController extends Controller
{
    private string $siigo_base_url = 'https://api.siigo.com';

    public function product_traceability()
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();
        $warehouses = $this->warehouses($token);

        return view('integration.product_traceability', compact('warehouses'));
    }

    public function product_traceability_download(Request $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();
        $warehouses = $this->warehouses($token);

        $request->validate([
            'warehouse_id' => 'required|string|in:' . collect($warehouses)->pluck('id')->join(','),
            'start_date' => 'required|date',
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $data = $this->movimientos_productos_bodegas($token, $request->integer('warehouse_id'), $request->input('start_date'), $warehouses);

        return $data = collect($data)->unique()->values()->all();
    }

    private function movimientos_productos_bodegas(string $token, int $warehouseId, string $startDate, array $warehouses)
    {
        $now = Carbon::now();
        $start = Carbon::parse($startDate);

        $periods = [];

        for ($year = 2015; $year <= $now->year; $year++) {
            $periods[] = [
                'id' => $year,
                'StartDate' => Carbon::create($year, 1, 1)->format('Ymd'),
                'EndDate' => Carbon::create($year, 12, 31)->format('Ymd'),
            ];
        }

        $warehouseSource = collect($warehouses)
            ->map(function ($warehouse) {
                return [
                    'id' => $warehouse['id'],
                    'name' => $warehouse['name'],
                ];
            })
            ->values()
            ->toArray();

        $selectedWarehouse = collect($warehouses)->firstWhere('id', $warehouseId);

        $filterCriterias = [
            [
                'Field' => 'AccountGroup',
                'FilterType' => 2,
                'OperatorType' => 0,
                'Value' => [-1],
                'ValueUI' => '',
                'Source' => json_encode([
                    [
                        'id' => 1337,
                        'name' => 'Compra de Calzados',
                    ],
                    [
                        'id' => 1189,
                        'name' => 'Elab. de Calzados ',
                    ],
                    [
                        'id' => 1338,
                        'name' => 'Materia Prima ',
                    ],
                    [
                        'id' => 1190,
                        'name' => 'Servicios Temporal',
                    ],
                ]),
            ],
            [
                'Field' => 'ElaborationDatePeriod',
                'FilterType' => 66,
                'OperatorType' => 9,
                'Value' => [
                    $start->format('Ymd'),
                    $now->format('Ymd'),
                    null,
                ],
                'ValueUI' => $start->format('Y/m/d') . ' - ' . $now->format('Y/m/d'),
                'Source' => json_encode($periods),
            ],
            [
                'Field' => 'ProductType',
                'FilterType' => 7,
                'OperatorType' => 0,
                'Value' => ['0'],
                'ValueUI' => 'Producto',
                'Source' => 'TypeProductEnum',
            ],
            [
                'Field' => 'State',
                'FilterType' => 7,
                'OperatorType' => 0,
                'Value' => [-1],
                'ValueUI' => '',
                'Source' => 'FixedAssetStateEnum',
            ],
            [
                'Field' => 'Product',
                'FilterType' => 6,
                'OperatorType' => 0,
                'Value' => [],
                'ValueUI' => '',
                'Source' => '2',
            ],
            [
                'Field' => 'WarehouseFilter',
                'FilterType' => 2,
                'OperatorType' => 0,
                'Value' => [$warehouseId],
                'ValueUI' => $selectedWarehouse['name'] ?? '',
                'Source' => json_encode($warehouseSource),
            ],
            [
                'Field' => 'IncludeWarehousesWithoutMovements',
                'FilterType' => 7,
                'OperatorType' => 0,
                'Value' => [-1],
                'ValueUI' => '',
                'Source' => 'LogicalEnum',
            ],
            [
                'Field' => 'Currency',
                'FilterType' => 65,
                'OperatorType' => 0,
                'Value' => ['ALL'],
                'ValueUI' => 'Moneda Local',
                'Source' => null,
            ],
        ];

        $body = [
            'Id' => 5412,
            'Skip' => 0,
            'Take' => 0,
            'Sort' => ' ',
            'FilterCriterias' => json_encode($filterCriterias),
            'Params' => json_encode([
                'TabID' => '1370',
                'pTabID' => '1445',
                'rReport' => '1',
            ]),
            'GetTotalCount' => false,
            'GridOrderCriteria' => null,
            'AddOns' => [],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando movimientos de productos: ' . $response->body()
            );
        }

        $data = $response->json('data.Value.Table');

        return $data;
    }

    private function warehouses(string $token)
    {
        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id' => 'consultadeFacturas',
        ])->get("{$this->siigo_base_url}/v1/warehouses");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        return $data;
    }
}

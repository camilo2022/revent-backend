<?php

namespace App\Services\Siigo;

use Carbon\Carbon;

/** Trazabilidad de productos (reporte 5412 de Siigo): movimientos de una bodega desde una fecha. */
class SiigoTraceabilityService
{
    /** Grupos de cuenta que consulta la trazabilidad por defecto. */
    private const DEFAULT_ACCOUNT_GROUPS = [
        ['id' => 1337, 'name' => 'Compra de Calzados'],
        ['id' => 1189, 'name' => 'Elab. de Calzados '],
        ['id' => 1338, 'name' => 'Materia Prima '],
        ['id' => 1190, 'name' => 'Servicios Temporal'],
    ];

    public function __construct(private SiigoReportService $reports)
    {
    }

    /**
     * Todos los movimientos de la bodega entre $startDate y hoy, de todos los productos.
     *
     * @param array<int,array{id:int|string,name:string}> $warehouses bodegas de Siigo (para el filtro)
     * @param array<int,array{id:int,name:string}>|null    $accountGroups grupos de cuenta a consultar
     */
    public function movements(string $token, int $warehouseId, string $startDate, array $warehouses, ?array $accountGroups = null): array
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
            ->map(fn ($warehouse) => [
                'id' => $warehouse['id'],
                'name' => $warehouse['name'],
            ])
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
                'Source' => json_encode($accountGroups ?? self::DEFAULT_ACCOUNT_GROUPS),
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

        return $this->reports->table($body, 'Error consultando movimientos de productos: ', $token) ?? [];
    }
}

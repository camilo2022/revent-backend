<?php

namespace App\Exports;

use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoInventoryReportService;
use App\Services\SiigoProductsCache;
use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use RuntimeException;

/**
 * Reporte de inventarios: una fila por producto y bodega con existencias.
 * Las existencias salen del reporte de inventario por bodega (una llamada por bodega, sin
 * paginar), los datos del producto del catálogo en caché y las fechas de ingreso de la
 * trazabilidad de cada referencia en esa bodega.
 */
class InventorySiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $name;
    protected $token;
    protected $stores;
    protected $filters;

    public function __construct($name, $token, $stores, $filters)
    {
        $this->name = $name;
        $this->token = $token;
        $this->stores = $stores;
        $this->filters = $filters;
    }

    public function headings(): array
    {
        return [
            'CUENTA DE GRUPO',
            'CODIGO',
            'DESCRIPCION',
            'NOMBRE',
            'COLOR',
            'PROVEEDOR',
            'CATEGORIA',
            'TALLA',
            'CODIGO_BARRAS',
            'MARCA',
            'MODELO',
            'ESTADO',
            'BODEGA',
            'CANTIDAD',
            'FECHA',
            'SEGUNDA_FECHA',
            'DIFERENCIA',
        ];
    }

    public function title(): string
    {
        return $this->name;
    }

    public function generator(): Generator
    {
        $report = app(SiigoInventoryReportService::class);

        $products = app(SiigoProductsCache::class)->keyedByProductId();

        if ($products->isEmpty()) {
            throw new RuntimeException('El catálogo de productos no está en caché. Ejecuta primero la actualización del catálogo y vuelve a generar el reporte.');
        }

        $warehouses = app(SiigoCatalogService::class)->warehouses($this->token);
        $excludedWarehouses = config('siigo.inventory.excluded_warehouses');
        $excludedGroups = config('siigo.inventory.excluded_account_groups');
        $positive = $this->filters['positive'] ?? true;

        foreach ($this->stores as $warehouseId => $store) {
            if (in_array($warehouseId, $excludedWarehouses, true)) {
                continue;
            }

            $stock = $report->stock($this->token, $warehouseId);

            $stock = array_values(array_filter($stock, function ($row) use ($positive) {
                $quantity = (float) ($row['QuantityBalance'] ?? 0);

                return $positive ? $quantity > 0 : $quantity < 0;
            }));

            if (empty($stock)) {
                continue;
            }

            $entryDates = $report->entryDates($this->token, (int) $warehouseId, $warehouses);

            foreach ($stock as $row) {
                $product = $products->get($row['productcode'] ?? null);

                if (in_array(mb_strtoupper(trim((string) ($product['accountGroupName'] ?? ''))), $excludedGroups, true)) {
                    continue;
                }

                $description = $product['description'] ?? ($row['Description'] ?? '');

                $parts = preg_split('/[*-]/', $description);

                $count = count($parts);

                $name = $parts[0] ?? '';
                $color = $parts[1] ?? '';
                $provider = '';
                $category = '';
                $size = '';

                if ($count === 3) {
                    $size = $parts[2] ?? '';
                } elseif ($count === 4) {
                    $category = $parts[2] ?? '';
                    $size = $parts[3] ?? '';
                } elseif ($count >= 5) {
                    $provider = $parts[$count - 3] ?? '';
                    $category = $parts[$count - 2] ?? '';
                    $size = $parts[$count - 1] ?? '';
                }

                [$firstDate, $secondDate] = $entryDates[SiigoInventoryReportService::reference($description)] ?? [null, null];

                $diffDays = ($firstDate && $secondDate) ? (new \DateTime($secondDate))->diff(new \DateTime($firstDate))->days : null;

                yield [
                    'CUENTA DE GRUPO' => $product['accountGroupName'] ?? null,
                    'CODIGO' => $product['code'] ?? null,
                    'DESCRIPCION' => $description,
                    'NOMBRE' => $name,
                    'COLOR' => $color,
                    'PROVEEDOR' => $provider,
                    'CATEGORIA' => $category,
                    'TALLA' => $size,
                    'CODIGO_BARRAS' => $product['codeBars'] ?? null,
                    'MARCA' => $product['brand'] ?? null,
                    'MODELO' => $product['model'] ?? null,
                    'ESTADO' => $store['status'] ?? 'APROBADO',
                    'BODEGA' => ($store['code'] ?? 'S/C') . ' - ' . ($store['name'] ?? null),
                    'CANTIDAD' => (float) $row['QuantityBalance'],
                    'FECHA' => $firstDate,
                    'SEGUNDA_FECHA' => $secondDate,
                    'DIFERENCIA' => $diffDays,
                ];
            }

            unset($stock, $entryDates);
        }
    }
}

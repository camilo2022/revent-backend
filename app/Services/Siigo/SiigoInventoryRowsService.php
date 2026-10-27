<?php

namespace App\Services\Siigo;

use App\Services\SiigoProductsCache;
use RuntimeException;

/**
 * Filas del reporte de inventarios: una por producto y bodega con existencias.
 * Cada bodega se consulta una sola vez y sus filas se reparten entre inventario
 * positivo y negativo, así un mismo libro puede llevar ambas hojas.
 */
class SiigoInventoryRowsService
{
    public const POSITIVE = 'positive';
    public const NEGATIVE = 'negative';

    public function __construct(
        private SiigoInventoryReportService $report,
        private SiigoCatalogService $catalog
    ) {
    }

    /**
     * @param array<int,array{name:string,code:string,status:string}> $stores bodegas indexadas por id (SiigoInventoryService::stores())
     * @param array<int,string>                                       $signs  POSITIVE y/o NEGATIVE
     * @return array<string,array<int,array>> signo => filas
     */
    public function build(string $token, array $stores, array $signs = [self::POSITIVE, self::NEGATIVE]): array
    {
        $products = app(SiigoProductsCache::class)->keyedByProductId();

        if ($products->isEmpty()) {
            throw new RuntimeException('El catálogo de productos no está en caché. Ejecuta primero la actualización del catálogo y vuelve a generar el reporte.');
        }

        $warehouses = $this->catalog->warehouses($token);
        $excludedWarehouses = config('siigo.inventory.excluded_warehouses');

        $rows = array_fill_keys($signs, []);

        foreach ($stores as $warehouseId => $store) {
            if (in_array($warehouseId, $excludedWarehouses, true)) {
                continue;
            }

            $stock = $this->report->stock($token, $warehouseId);

            $bySign = [];

            foreach ($signs as $sign) {
                $bySign[$sign] = array_filter($stock, function ($row) use ($sign) {
                    $quantity = (float) ($row['QuantityBalance'] ?? 0);

                    return $sign === self::POSITIVE ? $quantity > 0 : $quantity < 0;
                });
            }

            if (!array_filter($bySign)) {
                continue;
            }

            $entryDates = $this->report->entryDates($token, (int) $warehouseId, $warehouses);

            foreach ($bySign as $sign => $stockRows) {
                foreach ($stockRows as $stockRow) {
                    $row = $this->row($stockRow, $products->get($stockRow['productcode'] ?? null), $store, $entryDates);

                    if ($row !== null) {
                        $rows[$sign][] = $row;
                    }
                }
            }

            unset($stock, $bySign, $entryDates);
        }

        return $rows;
    }

    /** Una fila del Excel; null si el producto es de un grupo excluido. */
    private function row(array $stockRow, ?array $product, array $store, array $entryDates): ?array
    {
        $groupCode = isset($product['accountGroupCode']) ? (int) $product['accountGroupCode'] : null;

        if (in_array($groupCode, config('siigo.inventory.excluded_account_groups'), true)) {
            return null;
        }

        $description = $product['description'] ?? ($stockRow['Description'] ?? '');

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

        $diffDays = ($firstDate && $secondDate)
            ? (new \DateTime($secondDate))->diff(new \DateTime($firstDate))->days
            : null;

        return [
            'CUENTA DE GRUPO' => config("siigo.inventory.account_groups.{$groupCode}") ?? ($product['accountGroupName'] ?? null),
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
            'CANTIDAD' => (float) $stockRow['QuantityBalance'],
            'FECHA' => $firstDate,
            'SEGUNDA_FECHA' => $secondDate,
            'DIFERENCIA' => $diffDays,
        ];
    }
}

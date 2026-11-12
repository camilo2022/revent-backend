<?php

namespace App\Services\Integration;

use Illuminate\Support\Collection;

/**
 * Convierte el detalle de una orden de compra o factura de Siigo en la lista de ítems que usan recepción y seguimiento.
 *
 * Copiado de Integration\InvoicePurchaseOrderSiigoController::parse_items (commit b8694f73).
 * Si cambia ese método privado en el controlador legacy, hay que replicarlo aquí.
 */
class PurchaseOrderItemsParser
{
    public function parse(?array $detail, ?Collection $warehouses = null): array
    {
        return collect($detail['Items'] ?? [])
            ->filter(fn ($item) => !empty($item['ProductCode']))
            ->map(function ($item) use ($warehouses) {
                $parts = preg_split('/[-*]/', $item['LongDescription'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
                $parts = array_map('trim', $parts);
                $count = count($parts);
                $valid = $count === 5 || $count === 4;

                $warehouseCode = $item['WarehouseCode'] ?? null;
                $warehouseName = $warehouses?->get($warehouseCode)['name'] ?? null;

                return [
                    'ProductCode'        => $item['ProductCode'],
                    'Description'        => $item['Description'] ?? null,
                    'LongDescription'    => $item['LongDescription'] ?? null,
                    'WarehouseCode'      => $warehouseCode,
                    'Warehouse'          => $warehouseCode !== null
                        ? $warehouseCode . ' - ' . ($warehouseName ?? '')
                        : null,

                    'Reference'          => $valid ? $parts[0] : null,
                    'Color'              => $valid ? $parts[1] : null,
                    'Category'           => $valid ? $parts[$count - 2] : null,
                    'Size'               => $valid ? $parts[$count - 1] : null,

                    'Quantity'           => $item['Quantity'] ?? 0,
                    'UnitValue'          => ($item['UnitValue'] ?? 0) + ($item['TaxAddValue'] ?? 0) - ($item['TaxDiscValue'] ?? 0),
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
}

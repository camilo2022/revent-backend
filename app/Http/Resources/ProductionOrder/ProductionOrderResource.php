<?php

namespace App\Http\Resources\ProductionOrder;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductionOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return ['production_order' => self::map($this)];
    }

    // Fila del listado: cabecera con proveedor, ficha y totales (los totales los calcula el controlador)
    public static function row($order): array
    {
        return [
            'id' => $order->id,
            'consecutive' => $order->consecutive,
            'status' => $order->status,
            'due_date' => $order->due_date?->format('Y-m-d'),
            'vat_percentage' => $order->vat_percentage,
            'delivery_note_percentage' => $order->delivery_note_percentage,
            'supplier' => self::supplier($order),
            'technical_sheet' => self::sheet($order),
            'colors_count' => (int) $order->getAttribute('colors_count'),
            'stores_count' => (int) $order->getAttribute('stores_count'),
            'total_pairs' => (int) $order->getAttribute('total_pairs'),
            'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    // Detalle completo: colores, tiendas y cantidades por talla (usa las relaciones cargadas)
    public static function map($order): array
    {
        $productCode = $order->technical_sheet?->product?->code ?? 'NA';
        $stores = [];
        $pairs = 0;

        $details = $order->production_order_details->map(function ($detail) use ($productCode, &$stores, &$pairs) {
            $colorCode = $detail->product_detail?->assignable?->settings?->code ?? '00';

            // Cantidades agrupadas por tienda y con las tallas en orden natural (35, 36, 37…)
            $byStore = $detail->production_order_detail_quantities->groupBy('store_id')->map(function ($rows) use ($productCode, $colorCode, &$stores) {
                $store = $rows->first()->store;
                $stores[$store->id] = true;

                return [
                    'store' => ['id' => $store->id, 'code' => $store->code, 'name' => $store->name],
                    'total' => (int) $rows->sum('quantity'),
                    'sizes' => $rows->sortBy(fn ($row) => $row->product_detail?->assignable?->name, SORT_NATURAL)->map(fn ($row) => [
                        'product_detail_id' => $row->product_detail_id,
                        'code' => "{$productCode}-" . ($row->product_detail?->assignable?->settings?->code ?? '00') . "-{$colorCode}",
                        'name' => $row->product_detail?->assignable?->name,
                        'quantity' => (int) $row->quantity,
                    ])->values(),
                ];
            })->sortBy(fn ($store) => $store['store']['name'])->values();

            $total = (int) $detail->production_order_detail_quantities->sum('quantity');
            $pairs += $total;

            return [
                'id' => $detail->id,
                'product_detail_id' => $detail->product_detail_id,
                'code' => "{$productCode}-{$colorCode}",
                'color' => [
                    'id' => $detail->product_detail?->assignable?->id,
                    'name' => $detail->product_detail?->assignable?->name,
                    'code' => $colorCode,
                ],
                'cost' => $detail->cost,
                'price' => $detail->price,
                'observation' => $detail->observation,
                'total' => $total,
                'stores' => $byStore,
            ];
        })->values();

        return [
            'id' => $order->id,
            'consecutive' => $order->consecutive,
            'status' => $order->status,
            'due_date' => $order->due_date?->format('Y-m-d'),
            'vat_percentage' => $order->vat_percentage,
            'delivery_note_percentage' => $order->delivery_note_percentage,
            'supplier' => self::supplier($order),
            'technical_sheet' => self::sheet($order),
            'details' => $details,
            'totals' => ['pairs' => $pairs, 'colors' => $details->count(), 'stores' => count($stores)],
            'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $order->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private static function supplier($order): ?array
    {
        $supplier = $order->relationLoaded('supplier') ? $order->supplier : null;

        return $supplier ? ['id' => $supplier->id, 'code' => $supplier->code, 'legal_name' => $supplier->legal_name, 'trade_name' => $supplier->trade_name] : null;
    }

    private static function sheet($order): ?array
    {
        $sheet = $order->relationLoaded('technical_sheet') ? $order->technical_sheet : null;

        if (!$sheet) return null;

        return [
            'id' => $sheet->id,
            'version' => $sheet->version,
            'status' => $sheet->status,
            'product' => $sheet->relationLoaded('product') && $sheet->product
                ? ['id' => $sheet->product->id, 'code' => $sheet->product->code, 'name' => $sheet->product->name]
                : null,
        ];
    }
}

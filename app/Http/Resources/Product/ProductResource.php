<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return ['product' => self::map($this)];
    }

    // Forma única de un producto (la usan el recurso, el listado y la ficha técnica)
    public static function map($product): array
    {
        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'description' => $product->description,
            'cost' => $product->cost,
            'price' => $product->price,
            'collection_id' => $product->collection_id,
            'collection' => $product->relationLoaded('collection') && $product->collection ? [
                'id' => $product->collection->id,
                'name' => $product->collection->name,
                'year' => $product->collection->year,
                'code' => $product->collection->code,
                'product_prefix' => $product->collection->product_prefix,
            ] : null,
            'technical_sheet' => $product->relationLoaded('technical_sheet') && $product->technical_sheet ? [
                'id' => $product->technical_sheet->id,
                'status' => $product->technical_sheet->status,
            ] : null,
            'colors' => $product->relationLoaded('colors') ? $product->colors->map(fn ($color) => self::color($product, $color))->values() : [],
            'created_at' => $product->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $product->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    // Color con sus tallas: el código sale de las relaciones cargadas (colors.assignable y colors.sizes.assignable)
    private static function color($product, $color): array
    {
        $colorCode = $color->assignable?->settings?->code ?? '00';

        return [
            'id' => $color->id,
            'code' => "{$product->code}-{$colorCode}",
            'description' => $color->description,
            'color' => self::catalog($color->assignable),
            'sizes' => $color->sizes->map(fn ($size) => [
                'id' => $size->id,
                'code' => "{$product->code}-" . ($size->assignable?->settings?->code ?? '00') . "-{$colorCode}",
                'description' => $size->description,
                'size' => self::catalog($size->assignable),
            ])->values(),
        ];
    }

    private static function catalog($item): ?array
    {
        return $item ? ['id' => $item->id, 'name' => $item->name, 'settings' => $item->settings] : null;
    }
}

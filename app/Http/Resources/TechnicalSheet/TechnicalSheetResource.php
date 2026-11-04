<?php

namespace App\Http\Resources\TechnicalSheet;

use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicalSheetResource extends JsonResource
{
    public function toArray($request)
    {
        return ['technical_sheet' => self::map($this)];
    }

    // Forma única de una ficha técnica (la usan el recurso y el listado)
    public static function map($sheet): array
    {
        return [
            'id' => $sheet->id,
            'product_id' => $sheet->product_id,
            'status' => $sheet->status,
            'trademark_id' => $sheet->trademark_id,
            'category_id' => $sheet->category_id,
            'subcategory_id' => $sheet->subcategory_id,
            'process' => $sheet->process,
            'description' => $sheet->description,
            'observation' => $sheet->observation,
            // Siempre un objeto, aunque no tenga características
            'specifications' => (object) ($sheet->specifications ?? []),
            'approved_by' => $sheet->approved_by,
            'approved_at' => $sheet->approved_at?->format('Y-m-d H:i:s'),
            'created_at' => $sheet->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $sheet->updated_at?->format('Y-m-d H:i:s'),
            'product' => $sheet->relationLoaded('product') && $sheet->product ? ProductResource::map($sheet->product) : null,
            'trademark' => self::catalog($sheet, 'trademark'),
            'category' => self::catalog($sheet, 'category'),
            'subcategory' => self::catalog($sheet, 'subcategory'),
        ];
    }

    private static function catalog($sheet, string $relation): ?array
    {
        $item = $sheet->relationLoaded($relation) ? $sheet->{$relation} : null;

        return $item ? ['id' => $item->id, 'name' => $item->name, 'description' => $item->description, 'settings' => $item->settings] : null;
    }
}

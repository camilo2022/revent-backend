<?php

namespace App\Http\Resources\Collection;

use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray($request)
    {
        return ['collection' => self::map($this)];
    }

    // Forma única de una colección (la usan el recurso y el listado)
    public static function map($collection): array
    {
        return [
            'id' => $collection->id,
            'item_id' => $collection->item_id,
            'name' => $collection->name,
            'description' => $collection->description,
            'settings' => $collection->settings,
            'year' => $collection->year,
            'code' => $collection->code,
            'last_number' => $collection->last_number,
            'product_prefix' => $collection->product_prefix,
            'next_code' => $collection->next_code,
            'created_at' => $collection->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $collection->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $collection->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}

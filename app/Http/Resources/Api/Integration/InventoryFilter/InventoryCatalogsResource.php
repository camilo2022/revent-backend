<?php

namespace App\Http\Resources\Api\Integration\InventoryFilter;

use Illuminate\Http\Resources\Json\JsonResource;

/** Bodegas a las que el usuario tiene acceso y grupos de color del filtro. */
class InventoryCatalogsResource extends JsonResource
{
    public static function map($catalogs): array
    {
        return [
            'warehouses' => array_map(fn ($warehouse) => [
                'id' => $warehouse['id'],
                'name' => $warehouse['name'],
                'transito' => $warehouse['transito'] ?? false,
            ], $catalogs['warehouses']),
            'color_groups' => $catalogs['color_groups'],
        ];
    }

    public function toArray($request)
    {
        return ['catalogs' => self::map($this->resource)];
    }
}

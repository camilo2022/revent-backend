<?php

namespace App\Http\Resources\Api\Integration\ProductTraceability;

use Illuminate\Http\Resources\Json\JsonResource;

/** Bodegas reales de Siigo (sin SIN ASIGNAR ni tránsitos) que el usuario puede consultar; cada una indica si tiene bodega de tránsito. */
class WarehouseCollection extends JsonResource
{
    public static function map($warehouse): array
    {
        return [
            'id' => (int) $warehouse['id'],
            'name' => $warehouse['name'],
            'transito' => (bool) ($warehouse['transito'] ?? false),
        ];
    }

    public function toArray($request)
    {
        return ['warehouses' => collect($this->resource)->map(fn ($warehouse) => self::map($warehouse))->values()->all()];
    }
}

<?php

namespace App\Http\Resources\Api\Integration\CustomerSales;

use Illuminate\Http\Resources\Json\JsonResource;

/** Centros de costo de Siigo ordenados por nombre. */
class CostCenterCollection extends JsonResource
{
    public static function map($costCenter): array
    {
        return [
            'id' => $costCenter['id'] ?? null,
            'code' => $costCenter['code'] ?? null,
            'name' => $costCenter['name'] ?? null,
        ];
    }

    public function toArray($request)
    {
        return ['cost_centers' => collect($this->resource)->map(fn ($costCenter) => self::map($costCenter))->values()->all()];
    }
}

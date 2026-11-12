<?php

namespace App\Http\Resources\Api\Integration\MasivePurchaseOrders;

use Illuminate\Http\Resources\Json\JsonResource;

/** Tipos de orden de compra disponibles para la carga masiva. */
class PurchaseOrderTypeResource extends JsonResource
{
    public static function map($type): array
    {
        return [
            'id' => $type['id'],
            'name' => $type['name'],
        ];
    }

    public function toArray($request)
    {
        return ['types' => array_map(fn ($type) => self::map($type), $this->resource)];
    }
}

<?php

namespace App\Http\Resources\Api\Integration\PurchaseOrderTracking;

use Illuminate\Http\Resources\Json\JsonResource;

/** Órdenes de compra con sus ítems, lo confirmado en facturas de compra y lo pendiente. */
class PurchaseOrderTrackingResource extends JsonResource
{
    public static function map($orders): array
    {
        return array_values((array) $orders);
    }

    public function toArray($request)
    {
        return ['purchase_orders' => self::map($this->resource)];
    }
}

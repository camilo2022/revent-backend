<?php

namespace App\Http\Resources\Api\Integration\PurchaseOrderReception;

use Illuminate\Http\Resources\Json\JsonResource;

/** Orden de compra lista para recibir (null si no existe) con el acceso del usuario a cada una de sus bodegas. */
class PurchaseOrderReceptionResource extends JsonResource
{
    public static function map($order): ?array
    {
        return $order === null ? null : (array) $order;
    }

    public function toArray($request)
    {
        return ['invoice_purchase_order' => self::map($this->resource['order'])];
    }
}

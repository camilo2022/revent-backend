<?php

namespace App\Http\Resources\Api\Integration\AccountPayable;

use Illuminate\Http\Resources\Json\JsonResource;

/** Pagos de un proveedor agrupados por mes: [{mes, total, detalles[]}]. */
class PaymentsByMonthCollection extends JsonResource
{
    public function toArray($request)
    {
        return ['payments' => array_values($this->resource)];
    }
}

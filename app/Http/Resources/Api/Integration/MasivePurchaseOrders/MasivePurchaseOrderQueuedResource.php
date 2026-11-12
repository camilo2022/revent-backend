<?php

namespace App\Http\Resources\Api\Integration\MasivePurchaseOrders;

use Illuminate\Http\Resources\Json\JsonResource;

/** Carga de órdenes de compra encolada: el resultado llega al correo indicado. */
class MasivePurchaseOrderQueuedResource extends JsonResource
{
    public static function map($queued): array
    {
        return [
            'email' => $queued['email'],
            'referencia' => $queued['referencia'],
            'lines' => $queued['lines'],
        ];
    }

    public function toArray($request)
    {
        return ['upload' => self::map($this->resource)];
    }
}

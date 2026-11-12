<?php

namespace App\Http\Resources\Api\Integration\PurchaseOrderReception;

use Illuminate\Http\Resources\Json\JsonResource;

/** Recepción registrada: cuántas fotos de evidencia se guardaron y a quién se envió el correo. */
class ReceptionConfirmedResource extends JsonResource
{
    public static function map($reception): array
    {
        return [
            'images' => $reception['images'],
            'sent_to' => $reception['sent_to'],
        ];
    }

    public function toArray($request)
    {
        return ['reception' => self::map($this->resource)];
    }
}

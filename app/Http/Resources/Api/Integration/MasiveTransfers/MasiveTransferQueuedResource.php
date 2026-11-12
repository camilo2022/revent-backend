<?php

namespace App\Http\Resources\Api\Integration\MasiveTransfers;

use Illuminate\Http\Resources\Json\JsonResource;

/** Carga de traslados encolada: el resultado de cada traslado llega al correo indicado. */
class MasiveTransferQueuedResource extends JsonResource
{
    public static function map($queued): array
    {
        return [
            'email' => $queued['email'],
            'lines' => $queued['lines'],
        ];
    }

    public function toArray($request)
    {
        return ['upload' => self::map($this->resource)];
    }
}

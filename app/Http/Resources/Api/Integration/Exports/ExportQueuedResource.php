<?php

namespace App\Http\Resources\Api\Integration\Exports;

use Illuminate\Http\Resources\Json\JsonResource;

/** Exporte encolado: a qué correos llegará y cuántos trabajos se despacharon (el de inventario despacha uno por signo). */
class ExportQueuedResource extends JsonResource
{
    public static function map($queued): array
    {
        return [
            'emails' => array_values((array) $queued['emails']),
            'jobs' => $queued['jobs'],
        ];
    }

    public function toArray($request)
    {
        return ['export' => self::map($this->resource)];
    }
}

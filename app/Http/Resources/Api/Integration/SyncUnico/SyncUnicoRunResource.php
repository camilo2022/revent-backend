<?php

namespace App\Http\Resources\Api\Integration\SyncUnico;

use Illuminate\Http\Resources\Json\JsonResource;

/** Sincronización recién lanzada: su identificador en el historial y el rango que se envía a Único. */
class SyncUnicoRunResource extends JsonResource
{
    public static function map($run): array
    {
        return [
            'run_id' => $run['run_id'],
            'label' => $run['label'],
            'created_start' => $run['created_start'],
            'created_end' => $run['created_end'],
        ];
    }

    public function toArray($request)
    {
        return ['run' => self::map($this->resource)];
    }
}

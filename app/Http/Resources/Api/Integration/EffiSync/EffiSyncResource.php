<?php

namespace App\Http\Resources\Api\Integration\EffiSync;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Respuestas de la sincronización con Effi: el cuadre, el plan, el avance o la sincronización recién lanzada.
 * Se entregan tal como las arman los servicios de Effi (los mismos datos que usa la pantalla web).
 */
class EffiSyncResource extends JsonResource
{
    public function __construct($resource, private string $key)
    {
        parent::__construct($resource);
    }

    public function toArray($request)
    {
        return [$this->key => $this->resource];
    }
}

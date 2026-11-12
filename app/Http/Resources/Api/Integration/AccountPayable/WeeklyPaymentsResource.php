<?php

namespace App\Http\Resources\Api\Integration\AccountPayable;

use Illuminate\Http\Resources\Json\JsonResource;

/** Pagos agrupados por semana y proveedor dentro de un rango de fechas. */
class WeeklyPaymentsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'weekly' => [
                'desde' => $this->resource['desde'],
                'hasta' => $this->resource['hasta'],
                'weeks' => $this->resource['weeks'],
            ],
        ];
    }
}

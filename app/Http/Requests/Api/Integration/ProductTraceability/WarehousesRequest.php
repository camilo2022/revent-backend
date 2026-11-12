<?php

namespace App\Http\Requests\Api\Integration\ProductTraceability;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Bodegas disponibles para la trazabilidad: no recibe datos. */
class WarehousesRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}

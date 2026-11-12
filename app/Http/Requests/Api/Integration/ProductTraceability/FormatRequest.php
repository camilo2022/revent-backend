<?php

namespace App\Http\Requests\Api\Integration\ProductTraceability;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Descarga de la plantilla de trazabilidad: no recibe datos. */
class FormatRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}

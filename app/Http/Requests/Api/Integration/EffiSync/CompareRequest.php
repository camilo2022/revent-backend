<?php

namespace App\Http\Requests\Api\Integration\EffiSync;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Cuadre de la bodega WEB de Siigo contra Effi: no recibe datos. */
class CompareRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}

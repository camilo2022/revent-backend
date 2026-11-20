<?php

namespace App\Http\Requests\Api\Integration\EffiSync;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Plan de sincronización con Effi (solo consulta): no recibe datos. */
class PlanRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}

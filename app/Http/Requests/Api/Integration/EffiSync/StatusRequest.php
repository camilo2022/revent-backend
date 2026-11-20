<?php

namespace App\Http\Requests\Api\Integration\EffiSync;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Avance de la última sincronización con Effi: no recibe datos. */
class StatusRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}

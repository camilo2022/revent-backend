<?php

namespace App\Http\Requests\Api\Integration\SyncUnico;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Historial de sincronizaciones con Único: no recibe datos. */
class RunsRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}

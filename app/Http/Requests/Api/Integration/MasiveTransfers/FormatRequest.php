<?php

namespace App\Http\Requests\Api\Integration\MasiveTransfers;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Descarga del formato de traslados masivos: no recibe datos. */
class FormatRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}

<?php

namespace App\Http\Requests\Api\Integration\SearchDocument;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Búsqueda de una nota de traslado (23): requiere el token JWT del usuario de Siigo. */
class TransferNoteRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:100'],
            'token' => ['required', 'string', 'max:10000', $this->siigoToken()],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['query' => 'Documento'];
    }
}

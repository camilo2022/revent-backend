<?php

namespace App\Http\Requests\Api\Integration\Credentials;

use App\Http\Requests\Api\Integration\IntegrationRequest;

class CredentialsCheckRequest extends IntegrationRequest
{
    /** Solo valida que lleguen como texto: si la credencial es inválida el controlador responde valid=false, no un 422. */
    public function rules(): array
    {
        return [
            'token' => ['nullable', 'required_without:cookie', 'string', 'max:8000'],
            'cookie' => ['nullable', 'required_without:token', 'string', 'max:16000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Integration\AccountPayable;

class ConciliationRequest extends AccountPayableRequest
{
    public function rules(): array
    {
        return [
            'proveedor' => ['required', 'string', 'json'],
            'tipo' => ['required', 'integer'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'documentos' => ['required', 'string', 'json'],
            'recibos' => ['required', 'string', 'json'],
        ];
    }
}

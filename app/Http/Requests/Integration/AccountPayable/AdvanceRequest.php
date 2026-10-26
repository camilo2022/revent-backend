<?php

namespace App\Http\Requests\Integration\AccountPayable;

class AdvanceRequest extends AccountPayableRequest
{
    public function rules(): array
    {
        return [
            'proveedor' => ['required', 'string', 'json'],
            'tipo' => ['required', 'integer'],
            'accion' => ['required', 'string', 'max:100'],
            'origen' => ['required', 'integer'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'comprobante' => ['nullable', 'file', 'max:10240'],
        ];
    }
}

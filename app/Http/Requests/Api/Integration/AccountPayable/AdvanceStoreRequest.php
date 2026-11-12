<?php

namespace App\Http\Requests\Api\Integration\AccountPayable;

class AdvanceStoreRequest extends VoucherRequest
{
    public function rules(): array
    {
        return $this->provider() + [
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

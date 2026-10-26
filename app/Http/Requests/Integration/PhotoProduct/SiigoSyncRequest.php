<?php

namespace App\Http\Requests\Integration\PhotoProduct;

class SiigoSyncRequest extends PhotoProductRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'referencia' => ['required', 'string', 'max:100'],
        ];
    }
}

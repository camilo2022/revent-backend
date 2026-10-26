<?php

namespace App\Http\Requests\Integration\PhotoProduct;

class DeleteRequest extends PhotoProductRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'referencia' => ['required', 'string', 'max:100'],
            'filename' => ['required', 'string', 'max:255'],
        ];
    }
}

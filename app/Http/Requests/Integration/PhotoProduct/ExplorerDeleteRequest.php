<?php

namespace App\Http\Requests\Integration\PhotoProduct;

class ExplorerDeleteRequest extends PhotoProductRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'path' => ['required', 'string', 'max:200'],
            'filename' => ['required', 'string', 'max:255'],
        ];
    }
}

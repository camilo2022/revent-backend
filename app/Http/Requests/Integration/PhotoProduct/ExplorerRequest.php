<?php

namespace App\Http\Requests\Integration\PhotoProduct;

class ExplorerRequest extends PhotoProductRequest
{
    public function rules(): array
    {
        return [
            'path' => ['nullable', 'string', 'max:200'],
        ];
    }
}

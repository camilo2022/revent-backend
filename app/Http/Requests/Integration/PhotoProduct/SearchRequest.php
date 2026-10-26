<?php

namespace App\Http\Requests\Integration\PhotoProduct;

class SearchRequest extends PhotoProductRequest
{
    public function rules(): array
    {
        return [
            'referencia' => ['required', 'string', 'max:100'],
        ];
    }
}

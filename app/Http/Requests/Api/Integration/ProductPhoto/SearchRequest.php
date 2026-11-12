<?php

namespace App\Http\Requests\Api\Integration\ProductPhoto;

use Illuminate\Validation\Validator;

/** Fotos de una referencia. */
class SearchRequest extends ProductPhotoRequest
{
    public function rules(): array
    {
        return [
            'referencia' => ['required', 'string', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateReference($validator)];
    }
}

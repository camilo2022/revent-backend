<?php

namespace App\Http\Requests\Api\Integration\ProductPhoto;

use Illuminate\Validation\Validator;

/** Lista carpetas e imágenes de una ruta del explorador (products / REFERENCIA / COLOR). */
class ExplorerRequest extends ProductPhotoRequest
{
    public function rules(): array
    {
        return [
            'path' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateFolder($validator)];
    }
}

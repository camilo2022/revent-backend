<?php

namespace App\Http\Requests\Api\Integration\ProductPhoto;

use Illuminate\Validation\Validator;

/** Sube fotos a la carpeta de una referencia (se convierten a WebP). */
class UploadRequest extends ProductPhotoRequest
{
    public function rules(): array
    {
        return [
            'referencia' => ['required', 'string', 'max:100'],
            'photos' => ['required', 'array', 'min:1', 'max:' . self::MAX_PHOTOS],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateReference($validator)];
    }
}

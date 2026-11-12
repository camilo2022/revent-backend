<?php

namespace App\Http\Requests\Api\Integration\ProductPhoto;

use Illuminate\Validation\Validator;

/**
 * Sube fotos a la carpeta de un producto o de un color y las envía a Siigo. El permiso decide quién puede usar la función;
 * el token de Siigo (de cualquier usuario válido) solo se pide porque es Siigo quien recibe las fotos.
 */
class ExplorerUploadRequest extends ProductPhotoRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', $this->siigoToken()],
            'path' => ['required', 'string', 'max:200'],
            'photos' => ['required', 'array', 'min:1', 'max:' . self::MAX_PHOTOS],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateFolder($validator, true)];
    }
}

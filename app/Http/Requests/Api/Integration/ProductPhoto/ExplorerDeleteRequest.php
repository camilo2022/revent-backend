<?php

namespace App\Http\Requests\Api\Integration\ProductPhoto;

use Illuminate\Validation\Validator;

/** Elimina una foto de la carpeta de un producto o de un color. */
class ExplorerDeleteRequest extends ProductPhotoRequest
{
    public function rules(): array
    {
        return [
            'path' => ['required', 'string', 'max:200'],
            'filename' => $this->filenameRules(),
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'filename.regex' => 'El nombre del archivo no es válido.',
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->validateFolder($validator, true)];
    }
}

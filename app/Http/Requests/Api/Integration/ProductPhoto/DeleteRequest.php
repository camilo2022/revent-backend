<?php

namespace App\Http\Requests\Api\Integration\ProductPhoto;

use Illuminate\Validation\Validator;

/** Elimina una foto de la carpeta de una referencia. */
class DeleteRequest extends ProductPhotoRequest
{
    public function rules(): array
    {
        return [
            'referencia' => ['required', 'string', 'max:100'],
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
        return [fn (Validator $validator) => $this->validateReference($validator)];
    }
}

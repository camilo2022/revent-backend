<?php

namespace App\Http\Requests\Api\Integration\ProductPhoto;

use App\Services\ProductPhotoService;
use Illuminate\Validation\Validator;

/** Crea un producto (en products) o una carpeta de color (dentro de un producto). */
class ExplorerFolderRequest extends ProductPhotoRequest
{
    public function rules(): array
    {
        return [
            'path' => ['nullable', 'string', 'max:200'],
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $this->validateFolder($validator);

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $photos = app(ProductPhotoService::class);
            $level = $this->folder()['level'];
            $name = (string) $this->input('name');

            if ($level === 0 && !$photos->nombreValido($photos->normalizarNombre($name))) {
                $validator->errors()->add('name', 'Nombre inválido. Usa solo MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.');
            } elseif ($level === 1 && $photos->colorPorCarpeta($photos->normalizarColor($name)) === null) {
                $validator->errors()->add('name', 'El color seleccionado no existe en el catálogo.');
            } elseif ($level === 2) {
                $validator->errors()->add('path', 'No se pueden crear carpetas dentro de un color.');
            }
        }];
    }
}

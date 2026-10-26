<?php

namespace App\Http\Requests\Integration\PhotoProduct;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de las solicitudes del módulo de fotos de producto: mensajes y atributos comunes.
 * Usa el comportamiento por defecto de Laravel (redirect back / JSON 422 si es AJAX).
 */
abstract class PhotoProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'array' => 'Debe ser un arreglo.',
            'min.array' => 'Debes enviar al menos :min elemento.',
            'max.string' => 'Se permite máximo :max caracteres.',
            'max.file' => 'El archivo no debe superar :max KB.',
            'image' => 'Debe ser una imagen.',
            'mimes' => 'Debe ser un archivo de tipo: :values.',
            'file' => 'Debe ser un archivo.',
            'in' => 'El valor seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'Token',
            'referencia' => 'Referencia',
            'photos' => 'Fotos',
            'photos.*' => 'Foto',
            'path' => 'Ruta',
            'name' => 'Nombre',
            'filename' => 'Nombre del archivo',
            'zip' => 'Archivo ZIP',
            'zip_mode' => 'Modo del ZIP',
        ];
    }
}

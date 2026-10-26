<?php

namespace App\Http\Requests\Integration\InventoryFilter;

use Illuminate\Foundation\Http\FormRequest;

class ImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referencia' => ['required', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'integer' => 'Debe ser un número entero.',
            'min' => 'El valor mínimo es :min.',
            'max' => 'El valor máximo permitido es :max.',
        ];
    }

    public function attributes(): array
    {
        return [
            'referencia' => 'Referencia',
            'page' => 'Página',
            'per_page' => 'Resultados por página',
        ];
    }
}

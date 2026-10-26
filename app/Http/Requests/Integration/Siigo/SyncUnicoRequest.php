<?php

namespace App\Http\Requests\Integration\Siigo;

use Illuminate\Foundation\Http\FormRequest;

class SyncUnicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'created_start' => ['nullable', 'date', 'required_with:created_end'],
            'created_end' => ['nullable', 'date', 'required_with:created_start', 'after_or_equal:created_start'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required_with' => 'Es obligatorio cuando se indica el otro extremo del rango.',
            'date' => 'Debe ser una fecha válida.',
            'after_or_equal' => 'Debe ser igual o posterior a la fecha inicial.',
            'integer' => 'Debe ser un número entero.',
            'min' => 'El valor mínimo es :min.',
            'max' => 'El valor máximo permitido es :max.',
        ];
    }

    public function attributes(): array
    {
        return [
            'created_start' => 'Fecha inicial',
            'created_end' => 'Fecha final',
            'page' => 'Página',
            'page_size' => 'Tamaño de página',
        ];
    }
}

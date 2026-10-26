<?php

namespace App\Http\Requests\Integration\InventoryFilter;

use Illuminate\Foundation\Http\FormRequest;

class AllWarehousesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referencia' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'referencia' => 'Referencia',
        ];
    }
}

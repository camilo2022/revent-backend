<?php

namespace App\Http\Requests\Integration\InventoryFilter;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'integer' => 'Debe ser un número entero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'Bodega',
        ];
    }
}

<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductAllRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors()
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'numeric'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:id,trademark_id,code,category_id,subcategory_id,observation,created_at,updated_at,deleted_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'in' => 'Valor inválido. Permitidos: :values.'
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por pagina.',
            'page' => 'N° de pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Orden de datos.'
        ];
    }
}

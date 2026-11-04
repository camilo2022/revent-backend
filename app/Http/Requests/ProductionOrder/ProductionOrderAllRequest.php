<?php

namespace App\Http\Requests\ProductionOrder;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\ProductionOrder;

class ProductionOrderAllRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:id,consecutive,due_date,status,created_at,updated_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'status' => ['nullable', 'string', 'in:' . implode(',', ProductionOrder::statuses())],
            'supplier_id' => ['nullable', 'exists:suppliers,id']
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'Debe ser una cadena de texto.',
            'integer' => 'Debe ser un número entero.',
            'min' => 'El valor debe ser mayor o igual a :min.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'exists' => 'No registrado en el sistema.'
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por pagina.',
            'page' => 'N° de pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Orden de datos.',
            'status' => 'Estado',
            'supplier_id' => 'Proveedor'
        ];
    }
}

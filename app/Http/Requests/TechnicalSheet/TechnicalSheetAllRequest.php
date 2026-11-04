<?php

namespace App\Http\Requests\TechnicalSheet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\TechnicalSheet;

class TechnicalSheetAllRequest extends FormRequest
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
            'column' => ['nullable', 'string', 'in:id,version,status,approved_at,created_at,updated_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'product_id' => ['nullable', 'exists:products,id'],
            'status' => ['nullable', 'string', 'in:' . implode(',', TechnicalSheet::statuses())],
            'only_approved' => ['nullable', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'Debe ser una cadena de texto.',
            'integer' => 'Debe ser un número entero.',
            'min' => 'El valor debe ser mayor o igual a :min.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'exists' => 'No registrado en el sistema.',
            'boolean' => 'Deber ser verdadero o falso.'
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
            'product_id' => 'Producto',
            'status' => 'Estado',
            'only_approved' => 'Solo aprobadas'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['only_approved' => $this->boolean('only_approved')]);
    }
}

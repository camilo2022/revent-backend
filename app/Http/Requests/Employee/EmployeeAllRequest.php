<?php

namespace App\Http\Requests\Employee;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeAllRequest extends FormRequest
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
            'column' => ['nullable', 'string', 'in:id,person_id,operation_center,position_id,risk_manager_id,health_entity_id,pension_fund_id,compensation_fund_id,start_date,end_date,created_at,updated_at,deleted_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean'],
            'with_user' => ['nullable', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'in' => 'Valor inválido. Permitidos: :values.',
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
            'with_trashed' => 'Registros inactivos.',
            'with_user' => 'Usuario asociado'

        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
        if ($this->has('with_user')) {
            $this->merge(['with_user' => $this->boolean('with_user')]);
        }
    }
}

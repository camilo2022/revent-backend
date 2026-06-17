<?php

namespace App\Http\Requests\Authorization\Permission;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PermissionAllRequest extends FormRequest
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
            'role_id' => ['nullable', 'numeric', 'exists:roles,id'],
            'per_page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:id,name,title,description,created_at,updated_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc']
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.numeric' => 'Debe ser un valor numérico.',
            'search.string' => 'Debe ser un registro válido de busqueda. ',
            'column.string' => 'Debe ser una columna válida. ',
            'column.in' => 'El valor de la columna no es válido. ',
            'dir.string' => 'Debe ser un valor de orden válido. ',
            'dir.in' => 'El valor del orden no es válido. ',
        ];
    }

    public function attributes(): array
    {
        return [
            'role_id' => '',
            'per_page' => 'Registros por pagina.',
            'search' => 'Filtro de Busqueda.',
            'column' => 'Columna a ordenar.',
            'dir' => 'Sentido del orden.',

        ];
    }
}

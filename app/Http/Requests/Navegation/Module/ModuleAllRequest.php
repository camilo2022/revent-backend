<?php

namespace App\Http\Requests\Navegation\Module;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ModuleAllRequest extends FormRequest
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
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:id,name,icon,created_at,updated_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean']
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
            'per_page' => 'Registros por pagina. ',
            'search' => 'Filtro de Busqueda. ',
            'column' => 'Columna a ordenar. ',
            'dir' => 'Orden de datos. ',
            'with_trashed' => 'Registros inactivos.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
    }
}

<?php

namespace App\Http\Requests\Report;

use App\Models\Report;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReportAllRequest extends FormRequest
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
            'search' => ['nullable', 'string'],
            'per_page' => ['nullable', 'numeric'],
            'page' => ['nullable', 'numeric'],
            'column' => ['nullable', 'string', 'in:id,name,created_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'El campo debe ser texto.',
            'numeric' => 'El campo debe ser un número.',
            'in' => 'El valor no es válido. Permitidos: :values.',
            'boolean' => 'El campo debe ser verdadero o falso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'search' => 'Filtro de búsqueda',
            'per_page' => 'Registros por página',
            'page' => 'N° de página',
            'column' => 'Columna a ordenar',
            'dir' => 'Orden de datos',
            'with_trashed' => 'Registros inactivos',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('with_trashed')) {
            $this->merge(['with_trashed' => filter_var($this->input('with_trashed'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }
}

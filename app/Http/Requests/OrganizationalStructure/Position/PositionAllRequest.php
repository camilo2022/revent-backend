<?php

namespace App\Http\Requests\OrganizationalStructure\Position;

use App\Models\Area;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PositionAllRequest extends FormRequest
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
            'area_id' => ['nullable', 'numeric', 'exists:subitems,id,item_id,' . Area::ITEM_ID],
            'per_page' => ['nullable', 'numeric'],
            'page' => ['nullable', 'numeric'],
            'search' => ['nullable', 'string'],
            'column' => ['nullable', 'string', 'in:id,name,description,created_at,updated_at,deleted_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'with_trashed' => ['required', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'exists' => 'No hay ningún registro.',
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
            'with_trashed' => 'Registros inactivos.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['with_trashed' => $this->boolean('with_trashed')]);
    }
}

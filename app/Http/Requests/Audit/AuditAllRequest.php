<?php

namespace App\Http\Requests\Audit;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuditAllRequest extends FormRequest
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
            'user_id' => ['nullable', 'numeric'],
            'column' => ['nullable', 'string', 'in:created_at,event'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'event' => ['nullable', 'string', 'in:created,updated,deleted,restored,attach,detach,sync'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un valor numérico.',
            'in' => 'Valor inválido. Permitidos: :values.',
            'boolean' => 'Deber ser verdadero o falso.',
            'date' => 'Debe se una fecha'
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
            'event' => 'Tipo de evento.',
            'start_date' => 'Fecha inicio de rango',
            'end_date' => 'Fecha fin de rango'
        ];
    }
}

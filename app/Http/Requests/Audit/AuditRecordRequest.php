<?php

namespace App\Http\Requests\Audit;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuditRecordRequest extends FormRequest
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
            'event' => ['nullable', 'string', 'in:created,updated,deleted,restored,attach,detach,sync'],
        ];
    }

    public function messages(): array
    {
        return [
            'string' => 'El campo debe ser texto.',
            'numeric' => 'El campo debe ser un número.',
            'in' => 'El valor no es válido. Permitidos: :values.',
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'Registros por página',
            'page' => 'N° de página',
            'event' => 'Tipo de evento',
        ];
    }
}

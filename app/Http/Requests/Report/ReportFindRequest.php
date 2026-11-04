<?php

namespace App\Http\Requests\Report;

use App\Models\Report;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReportFindRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:subitems,id,item_id,' . Report::ITEM_ID . ',deleted_at,NULL'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo es requerido.',
            'integer' => 'El campo debe ser un número entero.',
            'exists' => 'El valor seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return ['id' => 'Identificador del reporte'];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

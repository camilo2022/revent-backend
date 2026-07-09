<?php

namespace App\Http\Requests\Location\City;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CityFindRequest extends FormRequest
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
            'id' => ['required', 'exists:cities,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'exists' => 'No hay ningún registro.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la ciudad'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

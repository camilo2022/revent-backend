<?php

namespace App\Http\Requests\Navegation\Module;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ModuleStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:18', 'unique:modules,name', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\.?\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/'],
            'icon' => ['required', 'string', 'max:40', 'regex:/^Fa([A-Z][a-zA-Z0-9]*|[0-9][a-zA-Z0-9]*)$/']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'regex' => 'Formato inválido. ',
            'string' => 'Debe ser una cadena de texto.',
            'unique' => 'Ya está registrado.',
            'max' => 'Se permite máximo :max caracteres.'
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre del módulo',
            'icon' => 'Icono del módulo',
        ];
    }
}

<?php

namespace App\Http\Requests\Authorization\Role;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'unique:roles,name,' . $this->route('id') . ',id', 'max:80', 'regex:/^[a-z_]+$/'],
            'title' => ['required', 'string', 'unique:roles,title,' . $this->route('id') . ',id', 'max:100'],
            'description' => ['required', 'string', 'unique:roles,description,' . $this->route('id') . ',id', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'exists' => 'No hay ningún registro.',
            'required' => 'Es obligatorio.',
            'regex' => 'Formato inválido. Solo permite letras minúsculas y sin espacios.',
            'string' => 'Debe ser una cadena de texto.',
            'unique' => 'Ya está registrado.',
            'max' => 'Se permite maximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del rol',
            'name' => 'Nombre del rol',
            'title' => 'Título del rol',
            'description' => 'Descripción del rol'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

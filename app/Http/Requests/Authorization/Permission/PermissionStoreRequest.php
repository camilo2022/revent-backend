<?php

namespace App\Http\Requests\Authorization\Permission;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PermissionStoreRequest extends FormRequest
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
            'role_id' => ['required', 'numeric', 'exists:roles,id'],
            'name' => ['required', 'string', 'unique:permissions,name', 'max:80', 'regex:/^[a-z._]+$/'],
            'title' => ['required', 'string', 'unique:permissions,title', 'max:100'],
            'description' => ['required', 'string', 'unique:permissions,description', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'exists' => 'No hay ningún registro.',
            'required' => 'Es obligatorio.',
            'regex' => 'Formato inválido. Solo permite letras minúsculas y punto.',
            'string' => 'Debe ser una cadena de texto.',
            'numeric' => 'Debe ser un dato númerico.',
            'unique' => 'Ya está registrado.',
            'max' => 'Se permite maximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'role_id' => 'Identificador del rol',
            'name' => 'Nombre del permiso',
            'title' => 'Título del permiso',
            'description' => 'Descripción del permiso',
        ];
    }
}

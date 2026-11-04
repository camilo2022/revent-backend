<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserStoreRequest extends FormRequest
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
            'employee_id' => ['required', 'numeric', 'exists:employees,id,deleted_at,NULL', 'unique:users,employee_id'],
            'username' => ['required', 'string', 'max:40', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'regex:~^(?=.*\p{Ll})(?=.*\p{Lu})(?=.*\d)(?=.*[@$!%*#?&._-]).{8,}$~u', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8', 'regex:~^(?=.*\p{Ll})(?=.*\p{Lu})(?=.*\d)(?=.*[@$!%*#?&._-]).{8,}$~u',]

        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo es requerido.',
            'regex' => "Formato inválido:\n- Al menos una letra mayúscula.\n- Al menos una letra minúscula.\n- Al menos un número.\n- Al menos un carácter especial.",
            'string' => 'El campo debe ser texto.',
            'unique' => 'El valor ya está registrado.',
            'exists' => 'El valor seleccionado no existe.',
            'max' => 'El campo permite máximo :max caracteres.',
            'min' => 'El campo requiere mínimo :min caracteres.',
            'confirmed' => 'Las contraseñas no coinciden.'
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'Empleado',
            'username' => 'Nombre de usuario',
            'password' => 'Contraseña del usuario',
            'password_confirmation' => 'Confirmación contraseña del usuario',
        ];
    }
}

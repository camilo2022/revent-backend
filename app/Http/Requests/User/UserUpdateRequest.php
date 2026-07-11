<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:users,id'],
            'employee_id' => ['required', 'numeric', 'exists:employees,id,deleted_at,NULL', 'unique:users,employee_id,' . $this->route('id') . ',id'],
            'username' => ['required', 'string', 'max:40', 'unique:users,username,' . $this->route('id') . ',id'],
            'password' => ['nullable', 'string', 'min:8', 'regex:~^(?=.*\p{Ll})(?=.*\p{Lu})(?=.*\d)(?=.*[@$!%*#?&._-]).{8,}$~u', 'confirmed'],
            'password_confirmation' => ['nullable', 'string', 'min:8', 'regex:~^(?=.*\p{Ll})(?=.*\p{Lu})(?=.*\d)(?=.*[@$!%*#?&._-]).{8,}$~u',]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'regex' => "Formato inválido:\n- Al menos una letra mayúscula.\n- Al menos una letra minúscula.\n- Al menos un número.\n- Al menos un carácter especial.",
            'string' => 'Debe ser una cadena de texto.',
            'unique' => 'Ya está registrado.',
            'exists' => 'No hay ningún registro.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'confirmed' => 'Las contraseñas no coinciden.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del usuario',
            'employee_id' => 'Empleado',
            'username' => 'Nombre de usuario',
            'password' => 'Contraseña del usuario',
            'password_confirmation' => 'Confirmación contraseña del usuario',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

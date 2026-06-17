<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserAuthorizationAssignRequest extends FormRequest
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
            'id' => ['required', 'numeric', 'exists:users,id'],
            'permission_id' => ['required', 'numeric', 'exists:permissions,id', 'unique:model_has_permissions,permission_id,NULL,NULL,model_type,' . User::class . ',model_id,' . $this->route('id')]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'numeric' => 'Debe ser un número.',
            'exists' => 'No hay ningún registro.',
            'unique' => 'Ya registrado.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del usuario',
            'permission_id' => 'Identificador del permiso'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

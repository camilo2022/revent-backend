<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserStoreRemoveRequest extends FormRequest
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
            'store_id' => ['required', 'numeric', 'exists:stores,id', 'exists:model_has_stores,store_id,model_type,' . User::class . ',model_id,' . $this->route('id')]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo es requerido.',
            'numeric' => 'El campo debe ser un número.',
            'exists' => 'El valor seleccionado no existe o no está asignado al usuario.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del usuario',
            'store_id' => 'Identificador de la tienda'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

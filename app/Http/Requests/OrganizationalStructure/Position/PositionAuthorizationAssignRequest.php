<?php

namespace App\Http\Requests\OrganizationalStructure\Position;

use App\Models\Position;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PositionAuthorizationAssignRequest extends FormRequest
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
            'id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Position::ITEM_ID],
            'permission_id' => ['required', 'numeric', 'exists:permissions,id', 'unique:model_has_permissions,permission_id,NULL,NULL,model_type,' . Position::class . ',model_id,' . $this->route('id')]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'numeric' => 'Debe ser un número.',
            'exists' => 'No hay ningún registro.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del cargo',
            'permission_id' => 'Identificador del permiso'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

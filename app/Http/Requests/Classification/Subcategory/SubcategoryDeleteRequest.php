<?php

namespace App\Http\Requests\Classification\Subcategory;

use App\Models\Subcategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubcategoryDeleteRequest extends FormRequest
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
            'id' => ['required', 'exists:subitems,id,deleted_at,NULL,item_id,' . Subcategory::ITEM_ID],
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
            'id' => 'Identificador de la subcategoría'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

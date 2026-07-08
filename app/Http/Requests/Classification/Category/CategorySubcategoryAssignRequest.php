<?php

namespace App\Http\Requests\Classification\Category;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CategorySubcategoryAssignRequest extends FormRequest
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
            'id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Category::ITEM_ID],
            'subcategory_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID, 'unique:model_has_subitems,model_id,NULL,NULL,model_type,' . Subcategory::class . ',subitem_id,' . $this->route('id')]
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
            'subcategory_id' => 'Identificador de la subcategoria'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

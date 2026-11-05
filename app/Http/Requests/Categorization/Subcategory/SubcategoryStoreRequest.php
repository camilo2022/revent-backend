<?php

namespace App\Http\Requests\Categorization\Subcategory;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubcategoryStoreRequest extends FormRequest
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
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . Subcategory::ITEM_ID],
            'category_id' => ['required', 'integer', 'exists:subitems,id,item_id,' . Category::ITEM_ID . ',deleted_at,NULL'],
            'description' => ['required', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'uppercase', 'string', 'size:2', 'unique:subitems,settings->code,NULL,id,item_id,' . Subcategory::ITEM_ID]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo es requerido.',
            'string' => 'El campo debe ser texto.',
            'max' => 'El campo permite máximo :max caracteres.',
            'min' => 'El campo requiere mínimo :min caracteres.',
            'size' => 'El campo debe tener :size caracteres.',
            'unique' => 'El valor ya está registrado.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'array' => 'El campo debe ser una lista.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'Categoría',
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }
}

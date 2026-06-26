<?php

namespace App\Http\Requests\Product;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Trademark;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:products,id'],
            'trademark_id' => ['required', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID],
            'code' => ['required', 'uppercase', 'string', 'size:8', 'unique:products,code,' . $this->route('id') . ',id'],
            'category_id' => ['required', 'exists:subitems,id,item_id,' . Category::ITEM_ID],
            'subcategory_id' => ['required', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID],
            'observation' => ['nullable', 'string', 'max:1000']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'size' => 'Debe tener :size caracteres.',
            'unique' => 'Ya está registrado.',
            'uppercase' => 'El campo debe estar en mayúsculas.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del producto',
            'trademark_id' => 'Identificador de la marca',
            'code' => 'Codigo - Referencia',
            'category_id' => 'Identificador de la categoria',
            'subcategory_id' => 'Identificador de la subcategoria',
            'observation' => 'Observacion'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

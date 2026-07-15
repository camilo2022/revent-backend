<?php

namespace App\Http\Requests\Product;

use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
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
            'trademark_id' => ['required', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID],
            'code' => ['required', 'uppercase', 'string', 'size:8', 'unique:products,code'],
            'category_id' => ['required', 'exists:subitems,id,item_id,' . Category::ITEM_ID],
            'subcategory_id' => ['required', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID],
            'description' => ['nullable', 'string', 'max:1000'],
            'colors_id' => ['required', 'array', 'min:1'],
            'colors_id.*' => ['array'],
            'colors_id.*.color_id' => ['required',  'distinct', 'exists:subitems,id,item_id,' . Color::ITEM_ID],
            'colors_id.*.description' => ['nullable', 'string', 'max:1000'],
            'colors_id.*.sizes_id' => ['required', 'array', 'min:1'],
            'colors_id.*.sizes_id.*' => ['array'],
            'colors_id.*.sizes_id.*.size_id' => ['required', 'distinct', 'exists:subitems,id,item_id,' . Size::ITEM_ID],
            'colors_id.*.sizes_id.*.description' => ['nullable', 'string', 'max:1000'],
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
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'array' => 'Debe ser un arreglo.',
            'exists' => 'El valor seleccionado no es válido.',
            'distinct' => 'Ya fue agregado.',
            'colors_id.min' => 'Se permite mínimo :min colores.',
            'colors_id.*.sizes_id.min' => 'Se permite mínimo :min tallas.'
        ];
    }

    public function attributes(): array
    {
        return [
            'trademark_id' => 'Identificador de la marca',
            'code' => 'Codigo - Referencia',
            'category_id' => 'Identificador de la categoria',
            'subcategory_id' => 'Identificador de la subcategoria',
            'description' => 'Descripción',
            'colors_id' => 'Colores',
            'colors_id.*.color_id' => 'Color',
            'colors_id.*.description' => 'Descripción del color',
            'colors_id.*.sizes_id' => 'Tallas',
            'colors_id.*.sizes_id.*.size_id' => 'Talla',
            'colors_id.*.sizes_id.*.description' => 'Descripción de la talla',
        ];
    }
}

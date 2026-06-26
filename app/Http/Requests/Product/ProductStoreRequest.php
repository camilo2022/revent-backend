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

class ProductStoreRequest extends FormRequest
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
            'observation' => ['nullable', 'string', 'max:1000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*' => ['array'],
            'details.*.color_id' => ['required', 'exists:subitems,id,item_id,' . Color::ITEM_ID],
            'details.*.size_id' => ['required', 'exists:subitems,id,item_id,' . Size::ITEM_ID]
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
            'trademark_id' => 'Identificador de la marca',
            'code' => 'Codigo - Referencia',
            'category_id' => 'Identificador de la categoria',
            'subcategory_id' => 'Identificador de la subcategoria',
            'observation' => 'Observacion'
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $combinations = [];
            foreach ($this->input('details', []) as $index => $detail) {
                $key = ($detail['color_id'] ?? '') . '-' . ($detail['size_id'] ?? '');
                if (isset($combinations[$key])) {
                    $validator->errors()->add(
                        "details.$index",
                        'La combinación de color y talla ya fue agregada.'
                    );
                }
                $combinations[$key] = true;
            }
        });
    }
}

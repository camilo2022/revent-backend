<?php

namespace App\Http\Requests\TechnicalSheet;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Trademark;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TechnicalSheetStoreRequest extends FormRequest
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
            'product_id' => ['required', 'exists:products,id', Rule::unique('technical_sheets', 'product_id')->whereNull('deleted_at')],
            'trademark_id' => ['required', 'integer', 'exists:subitems,id,item_id,' . Trademark::ITEM_ID . ',deleted_at,NULL'],
            'category_id' => ['required', 'integer', 'exists:subitems,id,item_id,' . Category::ITEM_ID . ',deleted_at,NULL'],
            'subcategory_id' => ['required', 'integer', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID . ',deleted_at,NULL'],
            'process' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'observation' => ['nullable', 'string', 'max:1000'],
            'specifications' => ['nullable', 'array'],
            'specifications.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo es requerido.',
            'exists' => 'El valor seleccionado no está registrado.',
            'unique' => 'El producto ya tiene ficha técnica.',
            'string' => 'El campo debe ser texto.',
            'integer' => 'El campo debe ser un número entero.',
            'array' => 'El campo debe ser una lista.',
            'max' => 'El campo permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'Producto',
            'trademark_id' => 'Marca',
            'category_id' => 'Categoría',
            'subcategory_id' => 'Subcategoría',
            'process' => 'Proceso',
            'description' => 'Descripción',
            'observation' => 'Observación',
            'specifications' => 'Características del calzado',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            // La subcategoría elegida debe estar asignada a la categoría
            $allowed = DB::table('model_has_subitems')
                ->where('model_type', Category::class)
                ->where('model_id', $this->input('category_id'))
                ->pluck('subitem_id');

            if (!$allowed->contains((int) $this->input('subcategory_id'))) {
                $validator->errors()->add('subcategory_id', 'La subcategoría no pertenece a la categoría elegida.');
            }
        });
    }

    protected function prepareForValidation()
    {
        // Sin características se guarda un objeto vacío
        if (!is_array($this->input('specifications'))) {
            $this->merge(['specifications' => []]);
        }
    }
}

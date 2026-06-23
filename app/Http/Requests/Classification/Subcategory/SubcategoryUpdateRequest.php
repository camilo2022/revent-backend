<?php

namespace App\Http\Requests\Classification\Subcategory;

use App\Models\Subcategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubcategoryUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:subitems,id,item_id,' . Subcategory::ITEM_ID],
            'name' => ['required', 'string', 'min:4', 'max:50', 'uppercase', 'unique:subitems,name,' . $this->route('id') . ',id,item_id,' . Subcategory::ITEM_ID],
            'description' => ['required', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'uppercase', 'string', 'size:2', 'unique:subitems,settings->code,NULL,id,item_id,' . Subcategory::ITEM_ID]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'size' => 'Debe tener :size caracteres.',
            'unique' => 'Ya está registrado.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'array' => 'Debe ser un arreglo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la categoría',
            'name' => 'Nombre',
            'description' => 'Descripción'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

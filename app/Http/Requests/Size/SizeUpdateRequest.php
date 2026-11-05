<?php

namespace App\Http\Requests\Size;

use App\Models\Size;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SizeUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:subitems,id,item_id,' . Size::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:2', 'max:50', Rule::unique('subitems', 'name')->where('item_id', Size::ITEM_ID)->ignore($this->route('id'))],
            'description' => ['required', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'uppercase', 'string', 'size:2', Rule::unique('subitems', 'settings->code')->where('item_id', Size::ITEM_ID)->ignore($this->route('id'))],
            'icon' => ['sometimes', 'array'],
            'icon.file' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
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
            'image' => 'El campo debe ser una imagen.',
            'mimes' => 'El archivo tiene una extensión inválida. Permitidas: :values.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuración',
            'settings.code' => 'Código',
            'icon' => 'Icono',
            'icon.file' => 'Archivo del icono',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

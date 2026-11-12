<?php

namespace App\Http\Requests\Color;

use App\Models\Color;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ColorUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:subitems,id,item_id,' . Color::ITEM_ID],
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', Rule::unique('subitems', 'name')->where('item_id', Color::ITEM_ID)->ignore($this->route('id'))],
            'description' => ['required', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'uppercase', 'string', 'size:2', Rule::unique('subitems', 'settings->code')->where('item_id', Color::ITEM_ID)->ignore($this->route('id'))],
            'settings.siigo_code' => ['nullable', 'integer', 'min:1', Rule::unique('subitems', 'settings->siigo_code')->where('item_id', Color::ITEM_ID)->ignore($this->route('id'))],
            'settings.hex' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'settings.macro_category' => ['nullable', 'string', 'max:50'],
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
            'integer' => 'El campo debe ser un número entero.',
            'settings.hex.regex' => 'El color debe ser hexadecimal, por ejemplo #1A2B3C.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuración',
            'settings.code' => 'Código',
            'settings.siigo_code' => 'Código en Siigo',
            'settings.hex' => 'Color (hexadecimal)',
            'settings.macro_category' => 'Grupo de color'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

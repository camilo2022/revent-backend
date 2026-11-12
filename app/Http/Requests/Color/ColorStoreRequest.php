<?php

namespace App\Http\Requests\Color;

use App\Models\Color;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ColorStoreRequest extends FormRequest
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
            'name' => ['required', 'uppercase', 'string', 'min:4', 'max:50', 'unique:subitems,name,NULL,id,item_id,' . Color::ITEM_ID],
            'description' => ['required', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.code' => ['required', 'uppercase', 'string', 'size:2', 'unique:subitems,settings->code,NULL,id,item_id,' . Color::ITEM_ID],
            'settings.siigo_code' => ['nullable', 'integer', 'min:1', 'unique:subitems,settings->siigo_code,NULL,id,item_id,' . Color::ITEM_ID],
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
            'settings.macro_category' => 'Grupo de color',
        ];
    }
}

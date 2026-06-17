<?php

namespace App\Http\Requests\Navegation\Submodule;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmoduleUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:submodules,id'],
            'module_id' => ['required', 'numeric', 'exists:modules,id'],
            'permission_id' => ['required', 'numeric', 'unique:submodules,permission_id,' . $this->route('id') . ',id', 'exists:permissions,id'],
            'name' => ['required', 'string', 'max:18', 'unique:submodules,name,' . $this->route('id') . ',id', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\.?\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/'],
            'url' => ['required', 'string', 'max:40', 'unique:submodules,url,' . $this->route('id') . ',id', 'regex:/^\/[a-z_]+(\/[a-z_]+)*\/?$/'],
            'icon' => ['required', 'string', 'max:40', 'regex:/^Fa([A-Z][a-zA-Z0-9]*|[0-9][a-zA-Z0-9]*)$/']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'regex' => 'Formato inválido. ',
            'string' => 'Debe ser una cadena de texto.',
            'unique' => 'Ya está registrado.',
            'max' => 'Se permite máximo :max caracteres.',
            'numeric' => 'Tipo de dato inválido. ',
            'exists' => 'No hay ningún registro.'
        ];
    }

    public function attributes(): array
    {
        return [
            'module_id' => 'Identificador del módulo',
            'permission_id' => 'Identificador del permiso',
            'name' => 'Nombre del submódulo',
            'url' => 'Ruta del sobmódulo',
            'icon' => 'Icono del módulo',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

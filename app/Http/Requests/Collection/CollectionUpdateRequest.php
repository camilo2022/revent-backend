<?php

namespace App\Http\Requests\Collection;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CollectionUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:subitems,id,item_id,' . Collection::ITEM_ID . ',deleted_at,NULL'],
            'name' => ['required', 'uppercase', 'string', 'min:3', 'max:60', Rule::unique('subitems', 'name')->where('item_id', Collection::ITEM_ID)->ignore($this->route('id'))],
            'description' => ['nullable', 'string', 'max:255'],
            'settings' => ['required', 'array'],
            'settings.year' => ['required', 'string', 'regex:/^\d{2}$/'],
            'settings.code' => ['required', 'string', 'regex:/^\d$/', Rule::unique('subitems', 'settings->code')->where('item_id', Collection::ITEM_ID)->where('settings->year', $this->input('settings.year'))->ignore($this->route('id'))]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo es requerido.',
            'string' => 'El campo debe ser texto.',
            'array' => 'El campo debe ser una lista.',
            'min' => 'El campo requiere mínimo :min caracteres.',
            'max' => 'El campo permite máximo :max caracteres.',
            'uppercase' => 'El campo debe estar en mayúsculas.',
            'regex' => 'Formato inválido. Año de 2 dígitos y código de 1 dígito.',
            'unique' => 'El valor ya está registrado.',
            'unique' => 'Ya está registrado (el año y el código no se pueden repetir).'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la colección',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'settings' => 'Configuración',
            'settings.year' => 'Año (2 dígitos)',
            'settings.code' => 'Código de la colección (1 dígito)'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            $collection = Collection::find($this->route('id'));
            $changed = $collection->year !== $this->input('settings.year') || $collection->code !== $this->input('settings.code');

            // El año y el código ya forman parte de los códigos de los productos
            if ($changed && Product::where('collection_id', $collection->id)->exists()) {
                $validator->errors()->add('settings.code', 'La colección ya tiene productos: no se puede cambiar su año ni su código.');
            }
        });
    }
}

<?php

namespace App\Http\Requests\Collection;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Collection;
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
            'id' => ['required', 'exists:collections,id,deleted_at,NULL'],
            'name' => ['required', 'uppercase', 'string', 'min:3', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'year' => ['required', 'string', 'regex:/^\d{2}$/'],
            'code' => ['required', 'string', 'regex:/^\d$/', Rule::unique('collections', 'code')->where('year', $this->input('year'))->whereNull('deleted_at')->ignore($this->route('id'))]
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'min' => 'El valor debe ser mayor o igual a :min.',
            'max' => 'Se permite máximo :max.',
            'uppercase' => 'Debe estar en mayúsculas.',
            'regex' => 'Formato inválido. Año de 2 dígitos y código de 1 dígito.',
            'unique' => 'Ya existe una colección con ese año y código.',
            'exists' => 'No hay ningún registro.'
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'year' => 'Año (2 dígitos)',
            'code' => 'Código de la colección (1 dígito)',
            'id' => 'Identificador de la colección'
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

            $collection = Collection::withCount('products')->find($this->route('id'));

            // El año y el código ya forman parte de los códigos de los productos
            if ($collection->products_count > 0 && ($collection->year !== $this->input('year') || $collection->code !== $this->input('code'))) {
                $validator->errors()->add('code', 'La colección ya tiene productos: no se puede cambiar su año ni su código.');
            }
        });
    }
}

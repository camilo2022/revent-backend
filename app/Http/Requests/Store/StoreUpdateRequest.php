<?php

namespace App\Http\Requests\Store;

use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:stores,id,deleted_at,NULL'],
            'code' => ['required', 'string', 'size:2', 'unique:stores,code,' . $this->route('id') . ',id'],
            'name' => ['required', 'string', 'max:100', 'unique:stores,name,' . $this->route('id') . ',id', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s.&,\-]+$/'],
            'location_type' => ['required', 'string', 'in:' . implode(',', [Country::class, Department::class, City::class])],
            'location_id' => ['required', 'numeric', match ($this->input('location_type')) {
                    Country::class  => 'exists:countries,id',
                    Department::class => 'exists:departments,id',
                    City::class => 'exists:cities,id',
                    default => 'nullable'
                }],
            'address' => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo es requerido.',
            'string' => 'El campo debe ser texto.',
            'numeric' => 'El campo debe ser un número.',
            'max' => 'El campo permite máximo :max caracteres.',
            'size' => 'Se permite la longitud de :value caracteres.',
            'unique' => 'El valor ya está registrado.',
            'regex' => 'Formato inválido. Solo letras, números, espacios y . & , -',
            'exists' => 'No hay ningún registro.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la tienda',
            'code' => 'Código',
            'name' => 'Nombre',
            'location_type' => 'Tipo de ubicación',
            'location_id' => match ($this->input('location_type')) {
                Country::class  => 'País',
                Department::class => 'Departamento',
                City::class => 'Ciudad',
                default => 'Ubicación',
            },
            'address' => 'Dirección',
            'neighborhood' => 'Barrio'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

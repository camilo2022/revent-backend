<?php

namespace App\Http\Requests\Supplier;

use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Supplier;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplierUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:suppliers,id,item_id'],
            'code' => ['required', 'string', 'size:2', 'unique:suppliers,code,' . $this->route('id') . ',id'],
            'legal_name' => ['required', 'string', 'max:100', 'unique:suppliers,legal_name,' . $this->route('id') . ',id', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'],
            'trade_name' => ['nullable', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'],
            'document_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . DocumentType::ITEM_ID . ',deleted_at,NULL'],
            'document' => ['required', 'string', 'digits_between:5,14', 'unique:people,document,' . $this->route('id') . ',id'],
            'location_type' => ['required', 'string', 'in:' . implode(',', [Country::class, Department::class, City::class])],
            'location_id' => ['required', 'numeric', match ($this->input('assignable_type')) {
                    Country::class  => 'exists:countries,id',
                    Department::class => 'exists:departments,id',
                    City::class => 'exists:cities,id',
                    default => 'nullable'
                }],
            'address' => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'phone_country_id' => ['required', 'numeric', 'exists:countries,id'],
            'phone' => ['required', 'string', 'digits:10'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:people,email,' . $this->route('id') . ',id'],
            'file' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'email' => 'Debe ser un correo electrónico válido.',
            'numeric' => 'Debe ser un dato númerico.',
            'digits_between' => 'Debe tener entre 5 y 14 dígitos.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'size' => 'Se permite la longitud de :value caracteres.',
            'unique' => 'Ya está registrado.',
            'regex' => "Formato inválido:\n- Solo letras.",
            'exists' => 'No hay ningún registro.',
            'image' => 'Debe ser una imagen.',
            'mimes' => 'Extensión inválida. Permitidas :values.',
            'file.max' => 'Se permite máximo :max KB.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del proveedor',
            'code' => 'Código',
            'legal_name' => 'Nombre legal',
            'trade_name' => 'Nombre comercial',
            'document_type_id' => 'Identificador del tipo de documento',
            'document' => 'Número de documento',
            'location_type' => 'Tipo de ubicación',
            'location_id' => match ($this->input('assignable_type')) {
                Country::class  => 'País',
                Department::class => 'Departamento',
                City::class => 'Ciudad',
                default => 'Ubicación',
            },
            'address' => 'Dirección',
            'neighborhood' => 'Barrio',
            'phone_country_id' => 'Identificador del país del teléfono',
            'phone' => 'Número de teléfono',
            'email' => 'Correo electrónico',
            'file' => 'Archivo'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

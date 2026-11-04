<?php

namespace App\Http\Requests\Supplier;

use App\Models\City;
use App\Models\Country;
use App\Models\FileType;
use App\Models\Department;
use App\Models\DocumentType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplierStoreRequest extends FormRequest
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
            'code' => ['required', 'string', 'size:2', 'unique:suppliers,code'],
            'legal_name' => ['required', 'string', 'max:100', 'unique:suppliers,legal_name', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s.&,\-]+$/'],
            'trade_name' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s.&,\-]+$/'],
            'document_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . DocumentType::ITEM_ID . ',deleted_at,NULL'],
            'document' => ['required', 'string', 'digits_between:5,14', 'unique:suppliers,document'],
            'location_type' => ['required', 'string', 'in:' . implode(',', [Country::class, Department::class, City::class])],
            'location_id' => ['required', 'numeric', match ($this->input('location_type')) {
                    Country::class  => 'exists:countries,id',
                    Department::class => 'exists:departments,id',
                    City::class => 'exists:cities,id',
                    default => 'nullable'
                }],
            'address' => ['required', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'phone_country_id' => ['required', 'numeric', 'exists:countries,id'],
            'phone' => ['required', 'string', 'digits:10', 'unique:suppliers,phone'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:suppliers,email'],
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'file_type_id' => ['required', 'exists:subitems,id,item_id,' . FileType::ITEM_ID . ',deleted_at,NULL']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'required_with' => 'Es obligatorio cuando se envía el archivo.',
            'string' => 'Debe ser una cadena de texto.',
            'email' => 'Debe ser un correo electrónico válido.',
            'numeric' => 'Debe ser un dato númerico.',
            'digits_between' => 'Debe tener entre 5 y 14 dígitos.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'size' => 'Se permite la longitud de :value caracteres.',
            'unique' => 'Ya está registrado.',
            'regex' => 'Formato inválido. Solo letras, números, espacios y . & , -',
            'exists' => 'No hay ningún registro.',
            'image' => 'Debe ser una imagen.',
            'mimes' => 'Extensión inválida. Permitidas :values.',
            'file.max' => 'Se permite máximo :max KB.'
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Código',
            'legal_name' => 'Nombre legal',
            'trade_name' => 'Nombre comercial',
            'document_type_id' => 'Identificador del tipo de documento',
            'document' => 'Número de documento',
            'location_type' => 'Tipo de ubicación',
            'location_id' => match ($this->input('location_type')) {
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
            'file' => 'Archivo',
            'file_type_id' => 'Tipo de archivo'
        ];
    }
}

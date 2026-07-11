<?php

namespace App\Http\Requests\Person;

use App\Models\BloodType;
use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Gender;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PersonStoreRequest extends FormRequest
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
            'names' => ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'],
            'last_names' => ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'],
            'document_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . DocumentType::ITEM_ID . ',deleted_at,NULL'],
            'document' => ['required', 'string', 'digits_between:5,14', 'unique:people,document'],
            'gender_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Gender::ITEM_ID . ',deleted_at,NULL'],
            'birth_date' => ['required', 'date', 'date_format:Y-m-d', 'before:now'],
            'blood_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . BloodType::ITEM_ID . ',deleted_at,NULL'],
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:people,email'],
            'photo' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048']
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
            'date' => 'Debe ser una fecha válida.',
            'date_format' => 'Formato inválido. Permitido :format.',
            'before' => 'Debe ser una fecha anterior a :date.',
            'image' => 'Debe ser una imagen.',
            'mimes' => 'Extensión inválida. Permitidas :values.',
            'photo.max' => 'Se permite máximo :max KB.'
        ];
    }

    public function attributes(): array
    {
        return [
            'names' => 'Nombres',
            'last_names' => 'Apellidos',
            'document_type_id' => 'Identificador del tipo de documento',
            'document' => 'Número de documento',
            'gender_id' => 'Identificador del género',
            'birth_date' => 'Fecha de nacimiento',
            'blood_type_id' => 'Identificador del tipo de sangre',
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
            'photo' => 'Foto'
        ];
    }
}

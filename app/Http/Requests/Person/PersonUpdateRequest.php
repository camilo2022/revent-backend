<?php

namespace App\Http\Requests\Person;

use App\Models\BloodType;
use App\Models\Gender;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PersonUpdateRequest extends FormRequest
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
            'id' => ['required', 'exists:people,id'],
            'document' => ['required', 'string', 'min:4', 'max:14', 'unique:people,document,' . $this->route('id') . ',id'],
            'names' => ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'],
            'last_names' => ['required', 'string', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/'],
            'gender_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Gender::ITEM_ID . ',deleted_at,NULL'],
            'birth_date' => ['required', 'date', 'date_format:Y-m-d', 'before:now'],
            'blood_type_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . BloodType::ITEM_ID . ',deleted_at,NULL'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string'],
            'photo' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
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
            'id' => 'Identificador de la persona',
            'document' => 'Número de documento',
            'names' => 'Nombres',
            'last_names' => 'Apellidos',
            'gender_id' => 'Genero',
            'birth_date' => 'Fecha de nacimiento',
            'blood_type_id' => 'Tipo de sangre',
            'address' => 'Dirección',
            'phone' => 'Número de teléfono',
            'photo' => 'Foto'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

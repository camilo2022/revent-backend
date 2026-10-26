<?php

namespace App\Http\Requests\Integration\AccountPayable;

use Illuminate\Foundation\Http\FormRequest;

class DocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'max:50'],
            'MsThirdPartyID' => ['required_unless:type,payment', 'nullable', 'string', 'max:50'],
            'all_data' => ['nullable', 'in:0,1,true,false'],
        ];
    }

    public function messages(): array
    {
        return [
            'required_unless' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'in' => 'El valor no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'Tipo',
            'MsThirdPartyID' => 'Identificador del tercero',
            'all_data' => 'Todos los datos',
        ];
    }
}

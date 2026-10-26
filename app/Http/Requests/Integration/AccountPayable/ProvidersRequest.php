<?php

namespace App\Http\Requests\Integration\AccountPayable;

use Illuminate\Foundation\Http\FormRequest;

class ProvidersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sync' => ['nullable', 'in:0,1,true,false'],
        ];
    }

    public function messages(): array
    {
        return [
            'in' => 'El valor no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'sync' => 'Sincronizar',
        ];
    }
}

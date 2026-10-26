<?php

namespace App\Http\Requests\Integration\MasivePurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class MasivePurchaseOrderUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'email' => ['required', 'email', 'max:255'],
            'referencia' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'file' => 'El campo :attribute debe ser un archivo.',
            'mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls).',
            'email' => 'Debe ser un correo electrónico válido.',
            'string' => 'El campo :attribute debe ser texto.',
            'max.file' => 'El archivo no puede pesar más de :max KB.',
            'max.string' => 'El campo :attribute permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'email' => 'correo',
            'referencia' => 'referencia',
        ];
    }
}

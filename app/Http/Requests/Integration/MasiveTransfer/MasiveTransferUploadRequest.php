<?php

namespace App\Http\Requests\Integration\MasiveTransfer;

use Illuminate\Foundation\Http\FormRequest;

class MasiveTransferUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'email' => [
                'required',
                'email',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@' . preg_quote(config('siigo.mail.domain'), '/') . '$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'file' => 'El campo :attribute debe ser un archivo.',
            'mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls).',
            'email.email' => 'Debe ser un correo electrónico válido.',
            'email.regex' => 'El correo :input debe pertenecer al dominio @' . config('siigo.mail.domain'),
            'max.file' => 'El archivo no puede pesar más de :max KB.',
            'max.string' => 'El campo :attribute permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'email' => 'correo',
        ];
    }
}

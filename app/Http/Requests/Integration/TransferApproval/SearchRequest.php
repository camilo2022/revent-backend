<?php

namespace App\Http\Requests\Integration\TransferApproval;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchRequest extends FormRequest
{
    /** Respuesta de error propia del módulo: {success: false, message: primer error, errors}. */
    protected function failedValidation(ValidatorContract $validator)
    {
        $errors = $validator->errors()->messages();

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => collect($errors)->flatten()->first() ?? 'Datos inválidos.',
            'errors' => $errors,
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'query' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'token',
            'query' => 'nota de traslado',
        ];
    }
}

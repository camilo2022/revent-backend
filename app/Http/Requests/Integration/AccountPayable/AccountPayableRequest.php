<?php

namespace App\Http\Requests\Integration\AccountPayable;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base de las solicitudes JSON de cuentas por pagar. Conserva la forma de error del módulo:
 * {message: primer error, errors}.
 */
abstract class AccountPayableRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->messages();

        throw new HttpResponseException(response()->json([
            'message' => collect($errors)->flatten()->first() ?? 'Datos inválidos.',
            'errors' => $errors,
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'json' => 'El campo :attribute debe ser un JSON válido.',
            'file' => 'El campo :attribute debe ser un archivo.',
            'min.numeric' => 'El campo :attribute debe ser como mínimo :min.',
            'max.file' => 'El archivo :attribute no debe superar :max KB.',
            'max.string' => 'El campo :attribute admite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'proveedor' => 'proveedor',
            'tipo' => 'tipo',
            'accion' => 'acción',
            'origen' => 'origen',
            'fecha' => 'fecha',
            'observaciones' => 'observaciones',
            'valor' => 'valor',
            'documentos' => 'documentos',
            'recibos' => 'recibos',
            'comprobante' => 'comprobante',
        ];
    }
}

<?php

namespace App\Http\Requests\Integration\SearchDocument;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de las búsquedas/PDF de documentos de Siigo. El tipo no se restringe aquí: el controlador
 * responde con su propio 422 ("Tipo de documento no soportado") con la lista de tipos válidos.
 */
abstract class SearchDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:100'],
            'type' => ['required'],
            'token' => ['required', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => 'Documento',
            'type' => 'Tipo de documento',
            'token' => 'Token',
        ];
    }
}

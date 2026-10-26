<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de las solicitudes de "enlace de acceso por correo" de los módulos protegidos de Siigo.
 * Usa el comportamiento por defecto de Laravel (redirect back con errores / JSON 422 si es AJAX).
 */
abstract class AccessLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:254'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'email' => 'Debe ser un correo electrónico válido.',
            'max' => 'Se permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'Correo electrónico',
        ];
    }
}

<?php

namespace App\Http\Requests\Api\Integration;

use App\Rules\Integration\SiigoMonolithCookie;
use App\Rules\Integration\SiigoUserToken;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base de los FormRequest de la API de integración: mismo formato de error que el resto del ERP
 * y acceso a las credenciales de Siigo ya validadas (token / cookie).
 */
abstract class IntegrationRequest extends FormRequest
{
    private ?SiigoUserToken $tokenRule = null;

    private ?SiigoMonolithCookie $cookieRule = null;

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

    /** Regla para el campo `token`; recuerda el usuario de Siigo para siigoUser(). */
    protected function siigoToken(?array $allowedIds = null): SiigoUserToken
    {
        return $this->tokenRule = new SiigoUserToken($allowedIds);
    }

    /** Regla para el campo `cookie`; recuerda el JWT y el usuario para siigoJwt() / siigoCookieUser(). */
    protected function siigoCookie(bool $validateUser = true): SiigoMonolithCookie
    {
        return $this->cookieRule = new SiigoMonolithCookie($validateUser);
    }

    /** Usuario de Siigo dueño del token validado: ['id', 'user', 'name']. */
    public function siigoUser(): ?array
    {
        return $this->tokenRule?->user();
    }

    /** JWT extraído de la cookie validada. */
    public function siigoJwt(): ?string
    {
        return $this->cookieRule?->jwt();
    }

    public function siigoCookieUser(): ?array
    {
        return $this->cookieRule?->user();
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo es requerido.',
            'required_without' => 'Envía al menos un valor.',
            'string' => 'El campo debe ser texto.',
            'numeric' => 'El campo debe ser un número.',
            'integer' => 'El campo debe ser un número entero.',
            'boolean' => 'Debe ser verdadero o falso.',
            'array' => 'El campo debe ser una lista.',
            'email' => 'El campo debe ser un correo electrónico válido.',
            'date' => 'El campo debe ser una fecha válida.',
            'date_format' => 'El formato de la fecha no es válido (:format).',
            'file' => 'El campo debe ser un archivo.',
            'mimes' => 'El archivo debe ser de tipo: :values.',
            'max' => 'El valor supera el máximo permitido (:max).',
            'min' => 'El valor es menor al mínimo permitido (:min).',
            'in' => 'El valor no es válido. Permitidos: :values.',
            'exists' => 'El valor seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'Token de Siigo',
            'cookie' => 'Cookie de Siigo',
        ];
    }
}

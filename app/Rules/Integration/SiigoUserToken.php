<?php

namespace App\Rules\Integration;

use App\Services\Siigo\SiigoUserService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Token de usuario de Siigo (JWT). Se valida contra Siigo en cada petición, igual que las pantallas de Integration,
 * sin guardar el token en ninguna parte. Una credencial inválida es un error de validación (422), nunca un 401:
 * el front cierra la sesión del ERP ante cualquier 401.
 */
class SiigoUserToken implements ValidationRule
{
    /** @var array{id: int, user: string, name: string}|null Usuario de Siigo dueño del token, si fue válido. */
    private ?array $user = null;

    /** @param int[]|null $allowedIds Ids de usuario de Siigo permitidos (null = cualquiera). */
    public function __construct(private ?array $allowedIds = null)
    {
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match('/^eyJ[\w-]+\.[\w-]+\.[\w-]+$/', trim($value))) {
            $fail('El token de Siigo no tiene el formato esperado. Cárgalo de nuevo en Autorización Siigo.');

            return;
        }

        $result = app(SiigoUserService::class)->fromToken(trim($value));

        if (!$result['success']) {
            $fail('El token de Siigo no es válido o venció. Cárgalo de nuevo en Autorización Siigo.');

            return;
        }

        if ($this->allowedIds !== null && !in_array($result['data']['id'], $this->allowedIds, true)) {
            $fail('El usuario de Siigo del token no está autorizado para esta acción.');

            return;
        }

        $this->user = $result['data'];
    }
}

<?php

namespace App\Rules\Integration;

use App\Services\Siigo\SiigoUserService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Cadena de cookies del monolito de Siigo (la que entrega el botón "Cookie" de la extensión).
 * Exige el JWT de TKNSGRDDREVENTCALZADOSAS, .ASPXAUTH y ASP.NET_SessionId; las cookies 67-... son opcionales
 * porque las pantallas de Integration funcionan sin ellas. Un valor inválido es un 422 (nunca un 401).
 */
class SiigoMonolithCookie implements ValidationRule
{
    private const JWT = '/(?:^|;\s*)TKNSGRDDREVENTCALZADOSAS=(eyJ[\w-]+\.[\w-]+\.[\w-]+)/';

    private ?string $jwt = null;

    private ?array $user = null;

    public function __construct(private bool $validateUser = true)
    {
    }

    /** JWT contenido en la cookie TKNSGRDDREVENTCALZADOSAS, si la cookie fue válida. */
    public function jwt(): ?string
    {
        return $this->jwt;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cookie = is_string($value) ? trim($value) : '';

        if (!preg_match(self::JWT, $cookie, $matches)
            || !preg_match('/(?:^|;\s*)\.ASPXAUTH=[^;\s]+/', $cookie)
            || !preg_match('/(?:^|;\s*)ASP\.NET_SessionId=[^;\s]+/', $cookie)) {
            $fail('La cookie de Siigo está incompleta. Debe incluir TKNSGRDDREVENTCALZADOSAS, .ASPXAUTH y ASP.NET_SessionId. Cárgala de nuevo en Autorización Siigo.');

            return;
        }

        if ($this->validateUser) {
            $result = app(SiigoUserService::class)->fromToken($matches[1]);

            if (!$result['success']) {
                $fail('La cookie de Siigo no es válida o venció. Cárgala de nuevo en Autorización Siigo.');

                return;
            }

            $this->user = $result['data'];
        }

        $this->jwt = $matches[1];
    }
}

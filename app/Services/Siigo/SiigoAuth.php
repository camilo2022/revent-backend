<?php

namespace App\Services\Siigo;

use Illuminate\Support\Facades\Http;

/**
 * Autenticación contra la API pública de Siigo.
 * Cada llamada a token() inicia sesión de nuevo (sin caché: el token cacheado vencía antes de lo esperado).
 */
class SiigoAuth
{
    /** Inicia sesión en cada llamada: el token no se guarda en caché. $fresh se conserva por compatibilidad. */
    public function token(bool $fresh = false): string
    {
        return $this->authenticate();
    }

    private function authenticate(): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Partner-Id' => config('siigo.partner_id'),
        ])->post(
            config('siigo.urls.api') . config('siigo.endpoints.api.auth'),
            [
                'username' => config('siigo.username'),
                'access_key' => config('siigo.access_key'),
            ]
        );

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json('access_token');
    }
}

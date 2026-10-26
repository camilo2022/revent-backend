<?php

namespace App\Services\Siigo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Autenticación contra la API pública de Siigo.
 * El token se guarda en caché para no autenticar en cada petición.
 */
class SiigoAuth
{
    public function token(bool $fresh = false): string
    {
        $key = config('siigo.token.cache_key');

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember(
            $key,
            now()->addMinutes(config('siigo.token.ttl')),
            fn () => $this->authenticate()
        );
    }

    public function forget(): void
    {
        Cache::forget(config('siigo.token.cache_key'));
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

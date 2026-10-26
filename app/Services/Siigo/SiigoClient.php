<?php

namespace App\Services\Siigo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Fábrica de clientes HTTP hacia Siigo. Centraliza hosts, cabeceras,
 * tiempos de espera y reintentos para que los controladores no los repitan.
 */
class SiigoClient
{
    public function __construct(private SiigoAuth $auth)
    {
    }

    /** Token de la API pública (con caché). */
    public function token(bool $fresh = false): string
    {
        return $this->auth->token($fresh);
    }

    /** Arma una URL absoluta: host ('api' | 'services' | 'nube' | 'monolith') + ruta. */
    public function url(string $host, string $path = ''): string
    {
        return config("siigo.urls.{$host}") . ($path === '' ? '' : '/' . ltrim($path, '/'));
    }

    /**
     * API pública (api.siigo.com). El token es el que entrega token().
     * Con reintentos, para consultas paginadas largas.
     */
    public function api(?string $token = null, int $timeout = 180): PendingRequest
    {
        return Http::baseUrl(config('siigo.urls.api'))
            ->retry(config('siigo.http.retries'), config('siigo.http.retry_sleep_ms'))
            ->timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $token ?? $this->token(),
                'Partner-Id' => config('siigo.partner_id'),
            ]);
    }

    /** Microservicios internos (services.siigo.com) con el token de la API pública. */
    public function services(?string $token = null): PendingRequest
    {
        return Http::baseUrl(config('siigo.urls.services'))
            ->withToken($token ?? $this->token())
            ->acceptJson()
            ->connectTimeout(config('siigo.http.connect_timeout'))
            ->timeout(config('siigo.http.timeout'));
    }

    /**
     * Servicios internos autenticados con el token de un usuario de Siigo
     * (el que pega la persona en pantalla), no con el de la API pública.
     */
    public function servicesAs(string $userToken): PendingRequest
    {
        return Http::baseUrl(config('siigo.urls.services'))
            ->withHeaders(['Authorization' => $userToken])
            ->acceptJson()
            ->connectTimeout(config('siigo.http.connect_timeout'))
            ->timeout(config('siigo.http.timeout'));
    }

    /** ERP monolito (ASPX). Requiere el token y la cookie de sesión del usuario. */
    public function monolith(string $token, string $cookie): PendingRequest
    {
        return Http::baseUrl(config('siigo.urls.monolith'))
            ->withToken($token)
            ->withHeaders(['Cookie' => $cookie])
            ->connectTimeout(config('siigo.http.connect_timeout'))
            ->timeout(config('siigo.http.timeout'))
            ->withoutRedirecting();
    }

    /** Datos del usuario dueño de un token de Siigo (LoadSettings). */
    public function userSettings(string $userToken): Response
    {
        return $this->servicesAs($userToken)
            ->get(config('siigo.endpoints.services.load_settings'));
    }

    /** Reporte genérico de Siigo (getreport). */
    public function report(string $token, array $body): Response
    {
        return $this->services($token)->post(config('siigo.endpoints.services.report'), $body);
    }

    /** Recorre una lista paginada de la API pública y devuelve todos los resultados. */
    public function paginate(string $endpoint, array $query = [], ?string $token = null): array
    {
        $token ??= $this->token();
        $page = $query['page'] ?? 1;
        $query['page_size'] = $query['page_size'] ?? 100;
        $results = [];
        $totalPages = $page;

        do {
            $response = $this->api($token)->get($endpoint, [...$query, 'page' => $page]);

            if ($response->status() === 429) {
                sleep(1);
                continue;
            }

            if (!$response->successful()) {
                throw new \Exception($response->body());
            }

            $data = $response->json();
            $results = array_merge($results, $data['results'] ?? []);

            $pagination = $data['pagination'] ?? null;
            $totalPages = $pagination
                ? (int) ceil($pagination['total_results'] / max(1, $pagination['page_size']))
                : $page;

            $page++;

            if ($page <= $totalPages) {
                usleep(config('siigo.http.page_pause_us'));
            }
        } while ($page <= $totalPages);

        return $results;
    }
}

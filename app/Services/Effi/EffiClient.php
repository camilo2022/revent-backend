<?php

namespace App\Services\Effi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Cliente de la API de Effi (solo consultas por ahora). */
class EffiClient
{
    private function http(): PendingRequest
    {
        $key = config('effi.api_key');

        if (empty($key)) {
            throw new \RuntimeException('Falta EFFI_API_KEY en el .env.');
        }

        // 429 = límite de 600 peticiones por minuto: se reintenta con espera
        return Http::baseUrl(config('effi.url'))
            ->withToken($key)
            ->acceptJson()
            ->timeout(90)
            ->retry(3, 2000, fn ($e) => $e instanceof \Illuminate\Http\Client\RequestException && $e->response->status() === 429, false);
    }

    /** Todos los artículos vigentes (100 por página; después de la primera, de a 5 páginas en paralelo). */
    public function articles(): array
    {
        $first = $this->articlesPage($this->http()->get('articulo', ['pagina' => 1, 'por_pagina' => 100]));
        $all = $first->json('data') ?? [];
        $total = (int) $first->json('paginacion.total_paginas', 1);

        foreach (array_chunk($total > 1 ? range(2, $total) : [], 5) as $chunk) {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn ($page) => $pool->as("p{$page}")->baseUrl(config('effi.url'))->withToken(config('effi.api_key'))
                    ->acceptJson()->timeout(90)->get('articulo', ['pagina' => $page, 'por_pagina' => 100]),
                $chunk
            ));

            foreach ($chunk as $page) {
                $response = $responses["p{$page}"];

                // Si una página falla en paralelo (p. ej. 429), se pide sola con reintento
                if (!$response instanceof Response || !$response->successful()) {
                    $response = $this->articlesPage($this->http()->get('articulo', ['pagina' => $page, 'por_pagina' => 100]));
                }

                array_push($all, ...($response->json('data') ?? []));
            }
        }

        return $all;
    }

    private function articlesPage(Response $response): Response
    {
        if (!$response->successful()) {
            throw new \RuntimeException('Effi respondió ' . $response->status() . ' consultando artículos: ' . ($response->json('mensaje') ?? $response->body()));
        }

        return $response;
    }

    /** Nombre de la bodega configurada, o null si no existe. */
    public function warehouseName(int $sucursal, int $bodega): ?string
    {
        $response = $this->http()->get('bodega', ['sucursal' => $sucursal, 'id' => $bodega]);

        $row = $response->successful() ? ($response->json('data.0') ?? null) : null;

        return $row ? "{$row['nombre_sucursal']} / {$row['nombre']}" : null;
    }
}

<?php

namespace App\Services\Effi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Cliente de la API de Effi: consultas y, para la sincronización, modificar/crear artículos y ajustes de inventario. */
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

    /** Catálogo simple (id => nombre) de una ruta de consulta: categoria_articulo, tarifa_precio... */
    public function catalog(string $route): array
    {
        $response = $this->http()->get($route, ['por_pagina' => 100]);

        if (!$response->successful()) {
            throw new \RuntimeException("Effi respondió {$response->status()} consultando {$route}: " . ($response->json('mensaje') ?? $response->body()));
        }

        return collect($response->json('data') ?? [])->pluck('nombre', 'id')->all();
    }

    /** Modifica un artículo (solo las claves que se envían; id obligatorio). */
    public function updateArticle(array $body): array
    {
        return $this->write('PUT', 'articulo', $body);
    }

    /** Crea una categoría de artículo. Effi NO controla duplicados por nombre: quien llame debe comprobar antes que no exista. */
    public function createCategory(string $name): array
    {
        return $this->write('POST', 'categoria_articulo', ['nombre' => mb_substr($name, 0, 80)]);
    }

    /** Anula un artículo (baja lógica: en Effi se puede reactivar desde el web). */
    public function annulArticle(int $id): array
    {
        return $this->write('DELETE', 'articulo', ['id' => $id]);
    }

    /** Crea un artículo. No lleva idempotency_key: no reintentar a ciegas si hay un error de red. */
    public function createArticle(array $body): array
    {
        return $this->write('POST', 'articulo', $body);
    }

    /** Crea un ajuste de inventario (idempotency_key obligatorio: reenviarlo no duplica el ajuste). */
    public function createAdjustment(array $body): array
    {
        return $this->write('POST', 'ajuste_inventario', $body, true);
    }

    private function write(string $method, string $route, array $body, bool $retryOnFailure = false): array
    {
        $request = $this->http()->asJson();

        if ($retryOnFailure) {
            $request = $request->retry(3, 3000, fn ($e) => !($e instanceof \Illuminate\Http\Client\RequestException) || $e->response->serverError() || $e->response->status() === 429, false);
        }

        $response = $request->send($method, $route, ['json' => $body]);

        // 409 en un ajuste = ya se había creado con ese idempotency_key: se toma como hecho
        if ($response->status() === 409 && $route === 'ajuste_inventario') {
            return ['ok' => true, 'repetido' => true, 'data' => $response->json('data')];
        }

        if (!$response->successful()) {
            throw new \RuntimeException("Effi respondió {$response->status()} en {$method} {$route}: " . ($response->json('mensaje') ?? $response->body()));
        }

        return $response->json() ?? [];
    }

    /** Nombre de la bodega configurada, o null si no existe. */
    public function warehouseName(int $sucursal, int $bodega): ?string
    {
        $response = $this->http()->get('bodega', ['sucursal' => $sucursal, 'id' => $bodega]);

        $row = $response->successful() ? ($response->json('data.0') ?? null) : null;

        return $row ? "{$row['nombre_sucursal']} / {$row['nombre']}" : null;
    }
}

<?php

namespace App\Jobs;

use App\Services\SiigoInventoryService;
use App\Services\SiigoProductsCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CacheProductsSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PAGE_SIZE = 100;
    private const FILTERS = '{"Type":"0,1"}';
    private const MAX_PAGES_SAFETY = 1000;

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 3600;


    public function handle(): void
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();
        $page = 1;
        $totalPages = null;
        $totalResults = null;
        $totalDownloaded = 0;
        $fields = ['accountGroupCode', 'accountGroupName', 'brand', 'code', 'codeBars', 'codeBin', 'description', 'descriptionBin', 'measureUnit', 'measurementUnitCode', 'minimumStock', 'model', 'productGUID', 'productID', 'unit'];

        do {
            $data = $this->fetchPage($token, $page);
            $pageResults = $data['results'] ?? [];

            if (!is_array($pageResults)) {
                throw new \RuntimeException("Respuesta inválida de Siigo en la página {$page}.");
            }

            if ($totalPages === null) {
                $totalResults = (int) data_get($data, 'pagination.totalResults', 0);
                $pageSize = (int) data_get($data, 'pagination.pageSize', self::PAGE_SIZE) ?: self::PAGE_SIZE;
                $totalPages = $totalResults > 0 ? (int) ceil($totalResults / $pageSize) : 0;

                if ($totalPages > self::MAX_PAGES_SAFETY) {
                    throw new \RuntimeException("Siigo reportó demasiadas páginas: {$totalPages}.");
                }

                if ($totalResults > 0 && empty($pageResults)) {
                    throw new \RuntimeException('Siigo reportó productos, pero la primera página está vacía.');
                }
            }

            if ($page > $totalPages || ($page < $totalPages && empty($pageResults))) {
                throw new \RuntimeException("Página {$page} vacía o fuera del rango esperado.");
            }

            $pageResults = array_map(fn (array $product) => array_intersect_key($product, array_flip($fields)), $pageResults);

            Cache::forever(SiigoProductsCache::PAGE_PREFIX . $page, $pageResults);
            $totalDownloaded += count($pageResults);

            Log::info("[CacheProductsSiigoJob] Página {$page}/{$totalPages} guardada: " . count($pageResults) . " productos (acumulado: {$totalDownloaded}/{$totalResults}).");

            unset($data, $pageResults);
            $page++;
        } while ($page <= $totalPages);

        if ($totalDownloaded !== $totalResults) {
            throw new \RuntimeException("Catálogo incompleto: Siigo reportó {$totalResults} productos y se descargaron {$totalDownloaded}.");
        }

        $previousMeta = Cache::get(SiigoProductsCache::META_KEY, []);
        $previousPages = (int) ($previousMeta['pages'] ?? 0);

        for ($oldPage = $totalPages + 1; $oldPage <= $previousPages; $oldPage++) {
            Cache::forget(SiigoProductsCache::PAGE_PREFIX . $oldPage);
        }

        Cache::forever(SiigoProductsCache::META_KEY, ['pages' => $totalPages, 'total' => $totalDownloaded]);

        Log::info('[CacheProductsSiigoJob] Catálogo de productos actualizado', [
            'total_productos' => $totalDownloaded,
            'total_reportado_por_siigo' => $totalResults,
            'total_paginas' => $totalPages,
        ]);
    }

    private function fetchPage(string $token, int $page): array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(60)
            ->retry(5, 10000)
            ->get('https://services.siigo.com/catalog/api/product', [
                'page' => $page,
                'pageSize' => self::PAGE_SIZE,
                'filters' => self::FILTERS,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Error consultando productos Siigo (página {$page}): " . $response->body());
        }

        $json = $response->json();

        if (!is_array($json) || !isset($json['results']) || !is_array($json['results'])) {
            throw new \RuntimeException("Respuesta inválida de Siigo en la página {$page}.");
        }

        return $json;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[CacheProductsSiigoJob] Falló el cacheo del catálogo de productos: ' . $exception->getMessage());
    }
}

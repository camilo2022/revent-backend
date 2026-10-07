<?php

namespace App\Jobs;

use App\Services\ProductPhotoService;
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
use Illuminate\Support\Facades\Storage;

class CacheProductsSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PAGE_SIZE = 100;
    private const FILTERS = '{"SortBy":"Description.asc","Type":"0,1"}';
    private const MAX_PAGES_SAFETY = 1000;

    private const CATALOG_URL = 'https://services.siigo.com/catalog/api';
    private const PHOTO_DISK = ProductPhotoService::DISK;
    private const PHOTO_BASE_PATH = ProductPhotoService::BASE_PATH;
    private const PHOTO_REFERENCE_REGEX = '/^[A-ZÑ0-9_]+$/u';
    private const PHOTO_COLOR_REGEX = '/^[A-ZÑ0-9_-]+$/u';
    private const EMPTY_GUID = '00000000-0000-0000-0000-000000000000';

    /** Máximo de productos por job de fotos (si una referencia tiene más, se divide en varios jobs). */
    private const PHOTO_JOB_CHUNK_SIZE = 300;
    /** Segundos de separación entre el inicio de cada job de fotos, para no golpear a Siigo de golpe. */
    private const PHOTO_JOB_STAGGER_SECONDS = 2;

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 3600;

    /** Productos sin foto agrupados por referencia (se reinicia en cada ejecución). */
    private array $photoCandidates = [];
    private array $referenceFolders = [];

    public function handle(): void
    {
        $this->photoCandidates = [];
        $this->referenceFolders = [];

        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();
        $page = 1;
        $totalPages = null;
        $totalResults = null;
        $totalDownloaded = 0;
        $fields = ['accountGroupCode', 'accountGroupName', 'brand', 'code', 'codeBars', 'codeBin', 'description', 'descriptionBin', 'measureUnit', 'measurementUnitCode', 'minimumStock', 'model', 'productGUID', 'productID', 'unit', 'priceList'];

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

            // Antes de recortar los campos: separar los productos sin foto cuya referencia tiene carpeta.
            foreach ($pageResults as $rawProduct) {
                if (is_array($rawProduct)) {
                    $this->collectPhotoCandidate($rawProduct);
                }
            }

            $pageResults = array_map(function (array $product) use ($fields) {
                $product = array_intersect_key($product, array_flip($fields));

                $product['price'] = collect(data_get($product, 'priceList', []))
                    ->flatMap(fn ($item) => $item['priceList'] ?? [])
                    ->firstWhere('priceListID', 7142)['value'] ?? null;

                unset($product['priceList']);

                return $product;
            }, $pageResults);

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

        // El catálogo ya quedó guardado. Un fallo al despachar las fotos no debe hacer fallar ni reintentar este job.
        try {
            $this->dispatchPhotoJobs();
        } catch (\Throwable $exception) {
            report($exception);
            Log::error('[CacheProductsSiigoJob] Falló el despacho de los jobs de fotos: ' . $exception->getMessage());
        }
    }

    private function fetchPage(string $token, int $page): array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(60)
            ->retry(5, 10000)
            ->get(self::CATALOG_URL . '/product', [
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

    // ------------------------------------------------------------------
    // Fotos: acumular candidatos y despachar un job por referencia
    // ------------------------------------------------------------------

    /**
     * Guarda como candidato al producto si no tiene foto (imagePosition vacío) y existe
     * una carpeta en el disco con su referencia (posición 0 de la descripción, sin acentos).
     */
    private function collectPhotoCandidate(array $product): void
    {
        $productId = (int) ($product['productID'] ?? 0);
        $imagePosition = trim((string) ($product['imagePosition'] ?? ''));

        if ($productId <= 0 || ($imagePosition !== '' && $imagePosition !== self::EMPTY_GUID)) {
            return; // ya tiene foto en Siigo
        }

        $parsed = $this->parseDescription((string) ($product['description'] ?? ''));

        if (!$parsed || !$this->referenceFolderExists($parsed['reference'])) {
            return;
        }

        $this->photoCandidates[$parsed['reference']][] = [
            'product_id' => $productId,
            'code' => (string) ($product['code'] ?? ''),
            'color' => $parsed['color'],
        ];
    }

    /** Despacha un SyncReferencePhotosSiigoJob por referencia (dividido en bloques si es muy grande). */
    private function dispatchPhotoJobs(): void
    {
        if (!$this->photoCandidates) {
            Log::info('[CacheProductsSiigoJob] Fotos: no hay productos sin foto con carpeta de referencia.');

            return;
        }

        $jobs = 0;
        $products = 0;

        foreach ($this->photoCandidates as $reference => $candidates) {
            foreach (array_chunk($candidates, self::PHOTO_JOB_CHUNK_SIZE) as $chunk) {
                SyncReferencePhotosSiigoJob::dispatch((string) $reference, $chunk)
                    ->delay(now()->addSeconds($jobs * self::PHOTO_JOB_STAGGER_SECONDS));

                $jobs++;
                $products += count($chunk);
            }
        }

        Log::info('[CacheProductsSiigoJob] Jobs de fotos despachados', [
            'referencias' => count($this->photoCandidates),
            'jobs' => $jobs,
            'productos_sin_foto' => $products,
        ]);

        $this->photoCandidates = []; // liberar memoria
    }

    /**
     * AGUILA-NEGRO-SEBA-BA-35 => referencia AGUILA, color NEGRO.
     * Separa por "-" o "*". Referencia y color se normalizan: sin acentos (BACARDÍ => BACARDI),
     * en mayúsculas y conservando la Ñ. En el color los espacios pasan a guion.
     */
    private function parseDescription(string $description): ?array
    {
        $parts = preg_split('/[-*]/', trim($description));

        if (!$parts || count($parts) < 2) {
            return null;
        }

        $reference = $this->normalizeText($parts[0]);
        $color = preg_replace('/\s+/', '-', $this->normalizeText($parts[1]));

        if (preg_match(self::PHOTO_REFERENCE_REGEX, $reference) !== 1
            || preg_match(self::PHOTO_COLOR_REGEX, $color) !== 1) {
            return null;
        }

        return ['reference' => $reference, 'color' => $color];
    }

    /** Quita acentos (Á→A, Ü→U...) pero conserva la Ñ, y pasa a mayúsculas. */
    private function normalizeText(string $text): string
    {
        $text = trim($text);

        // Unifica acentos descompuestos (letra + acento combinado, común en Mac) en un solo carácter.
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        }

        $text = strtr($text, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);

        return mb_strtoupper($text);
    }

    private function referenceFolderExists(string $reference): bool
    {
        return $this->referenceFolders[$reference]
            ??= Storage::disk(self::PHOTO_DISK)->exists(self::PHOTO_BASE_PATH . "/{$reference}");
    }
}

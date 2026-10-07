<?php

namespace App\Jobs;

use App\Services\ProductPhotoService;
use App\Services\SiigoInventoryService;
use App\Services\SiigoProductsCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

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
    private const PHOTO_SYNC_TIME_BUDGET = 3000; // segundos desde el inicio del job (el timeout es 3600)
    private const EMPTY_GUID = '00000000-0000-0000-0000-000000000000';

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 3600;

    /** Estado interno de la sincronización de fotos (se reinicia en cada ejecución). */
    private int $startedAt = 0;
    private ?string $photoToken = null;
    private array $photoCandidates = [];
    private array $referenceFolders = [];

    public function handle(): void
    {
        $this->startedAt = time();
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

        // El catálogo ya quedó guardado. Un fallo en las fotos no debe hacer fallar ni reintentar el job.
        try {
            $this->syncPhotos();
        } catch (\Throwable $exception) {
            report($exception);
            Log::error('[CacheProductsSiigoJob] Falló la sincronización de fotos: ' . $exception->getMessage());
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
    // Sincronización de fotos con Siigo
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

    private function syncPhotos(): void
    {
        if (!$this->photoCandidates) {
            Log::info('[CacheProductsSiigoJob] Fotos: no hay productos sin foto con carpeta de referencia.');

            return;
        }

        $this->photoToken = (new SiigoInventoryService())->auth();

        $stats = ['subido' => 0, 'omitido' => 0, 'sin_foto_local' => 0, 'error' => 0, 'pendiente_por_tiempo' => 0];

        foreach ($this->photoCandidates as $reference => $products) {
            $reference = (string) $reference;
            $jpgByColor = [];
            $rootJpg = false; // false = aún no consultada; null = la raíz no tiene fotos

            foreach ($products as $index => $product) {
                if (time() - $this->startedAt > self::PHOTO_SYNC_TIME_BUDGET) {
                    // El resto se procesa en la próxima ejecución (los que ya tienen foto se omiten).
                    $stats['pendiente_por_tiempo'] += $this->countRemainingCandidates($reference, $index);
                    Log::warning('[CacheProductsSiigoJob] Fotos: se alcanzó el tiempo máximo; el resto queda para la próxima ejecución.', $stats);

                    return;
                }

                try {
                    // Confirmar en Siigo que realmente no tiene foto.
                    if ($this->siigoProductHasPhoto($product['product_id'])) {
                        $stats['omitido']++;
                        continue;
                    }

                    $color = $product['color'];

                    if (!array_key_exists($color, $jpgByColor)) {
                        $jpgByColor[$color] = $this->firstPhotoAsJpg(self::PHOTO_BASE_PATH . "/{$reference}/{$color}");
                    }

                    $jpg = $jpgByColor[$color];

                    if ($jpg === null) {
                        if ($rootJpg === false) {
                            $rootJpg = $this->firstPhotoAsJpg(self::PHOTO_BASE_PATH . "/{$reference}");
                        }

                        $jpg = $rootJpg;
                    }

                    if ($jpg === null) {
                        $stats['sin_foto_local']++;
                        continue;
                    }

                    $this->siigoUploadPhoto($product['product_id'], $jpg);
                    $stats['subido']++;
                } catch (\Throwable $exception) {
                    $stats['error']++;
                    Log::warning("[CacheProductsSiigoJob] Fotos: error con el producto {$product['code']} ({$product['product_id']}): " . $exception->getMessage());
                }
            }

            unset($jpgByColor, $rootJpg);
        }

        Log::info('[CacheProductsSiigoJob] Sincronización de fotos terminada', $stats);
    }

    private function countRemainingCandidates(string $currentReference, int $currentIndex): int
    {
        $remaining = 0;
        $reached = false;

        foreach ($this->photoCandidates as $reference => $products) {
            if ((string) $reference === $currentReference) {
                $remaining += count($products) - $currentIndex;
                $reached = true;
            } elseif ($reached) {
                $remaining += count($products);
            }
        }

        return $remaining;
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

    /**
     * Primera foto (orden alfabético) que esté directamente en la carpeta, sin entrar a
     * subcarpetas, convertida a JPG. Null si la carpeta no existe o no tiene fotos.
     */
    private function firstPhotoAsJpg(string $path): ?string
    {
        $disk = Storage::disk(self::PHOTO_DISK);

        if (!$disk->exists($path)) {
            return null;
        }

        $files = collect($disk->files($path))
            ->filter(fn ($file) => preg_match('/\.(jpe?g|png|webp)$/i', $file) === 1)
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        $contents = $disk->get($files->first());

        if ($contents === null || $contents === '') {
            return null;
        }

        return (new ImageManager(new Driver()))
            ->decodeBinary($contents)
            ->encodeUsingFormat(Format::JPEG, quality: 90)
            ->toString();
    }

    /** Envía la petición con el token actual; si Siigo responde 401 renueva el token y reintenta una vez. */
    private function siigoRequest(callable $send): Response
    {
        $response = $send($this->photoToken);

        if ($response->status() === 401) {
            $this->photoToken = (new SiigoInventoryService())->auth();
            $response = $send($this->photoToken);
        }

        return $response;
    }

    private function siigoProductHasPhoto(int $productId): bool
    {
        $response = $this->siigoRequest(fn (string $token) => Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 2000, throw: false)
            ->get(self::CATALOG_URL . '/FileStorage/File/list', [
                'referenceCode' => $productId,
                'referenceType' => 0,
                'storageStrategy' => 1,
            ]));

        if (!$response->successful()) {
            throw new \RuntimeException("Siigo respondió {$response->status()} al listar la foto del producto {$productId}.");
        }

        $files = $response->json();

        return is_array($files) && !empty($files);
    }

    private function siigoUploadPhoto(int $productId, string $jpgContents): void
    {
        $fileName = (int) (microtime(true) * 1000) . '.jpg';

        $response = $this->siigoRequest(fn (string $token) => Http::withToken($token)
            ->acceptJson()
            ->timeout(60)
            ->retry(2, 2000, throw: false)
            ->attach('File', $jpgContents, $fileName, ['Content-Type' => 'image/jpeg'])
            ->post(self::CATALOG_URL . '/FileStorage/File', [
                'ReferenceType' => 0,
                'ReferenceCode' => $productId,
                'FileName' => $fileName,
                'IsMigrate' => 'true',
                'StorageStrategy' => 1,
            ]));

        if (!$response->successful()) {
            throw new \RuntimeException("Siigo respondió {$response->status()} al subir la foto del producto {$productId}.");
        }
    }
}

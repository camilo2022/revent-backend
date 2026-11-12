<?php

namespace App\Services\Integration;

use App\Services\ProductPhotoService;
use App\Services\Siigo\SiigoClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Envío de fotos de producto a Siigo con el token del usuario.
 * Copiado de Integration\PhotoProductSiigoController (reemplazar_foto_siigo_color, completar_fotos_siigo_referencia,
 * sincronizar_referencia_siigo, procesar_producto_siigo y los siigo_*). Nunca lanza excepciones hacia afuera:
 * devuelve el resultado para que un fallo de Siigo no afecte la carga local.
 */
class ProductPhotoSiigoService
{
    private const PAGE_SIZE = 100;

    private const MAX_PAGES = 1; // tope de seguridad para la paginación

    private const TIMEOUT = 60;

    public function __construct(
        private SiigoClient $client,
        private ProductPhotoService $photos,
        private ProductPhotoImageService $images
    ) {
    }

    /** Reemplaza la foto de todos los productos de Siigo de una referencia + color (carpeta de color). */
    public function replaceColorPhoto(string $token, string $reference, string $color, string $webpContents): array
    {
        try {
            // Referencia tal cual (nombre de la carpeta); solo el color se normaliza.
            $color = $this->normalizeKey($color);
            $jpg = $this->images->toJpg($webpContents);

            $results = [];

            foreach ($this->searchProducts($token, $reference) as $product) {
                $productId = (int) ($product['productID'] ?? 0);
                $description = (string) ($product['description'] ?? $product['name'] ?? '');
                $parsed = $this->parseDescription($description);

                if ($productId <= 0 || !$parsed || $parsed['reference'] !== $reference || $parsed['color'] !== $color) {
                    continue;
                }

                $results[] = [
                    'product_id' => $productId,
                    'code' => (string) ($product['code'] ?? ''),
                ] + $this->processProduct($token, $productId, $jpg, true);
            }

            $summary = array_count_values(array_column($results, 'status'));

            return [
                'success' => !isset($summary['error']),
                'products_found' => count($results),
                'summary' => $summary,
                'results' => $results,
                'message' => $results ? null : "No se encontraron productos en Siigo para {$reference} / {$color}.",
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'error' => 'No se pudo enviar la foto a Siigo: ' . Str::limit($exception->getMessage(), 200),
            ];
        }
    }

    /**
     * Carpeta raíz de la referencia: a los productos de Siigo que NO tienen foto les sube la primera foto de su color
     * o, si el color no tiene, la primera de la raíz. Nunca borra ni reemplaza fotos existentes.
     */
    public function completeReferencePhotos(string $token, string $reference): array
    {
        try {
            $data = $this->syncReference($token, $reference);

            return ['success' => !isset($data['summary']['error'])] + $data;
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'error' => 'No se pudo enviar la foto a Siigo: ' . Str::limit($exception->getMessage(), 200),
            ];
        }
    }

    private function syncReference(string $token, string $reference): array
    {
        $products = $this->searchProducts($token, $reference);

        $jpgByColor = [];
        $rootJpg = false; // false = aún no consultada; null = no hay foto en la raíz
        $results = [];

        foreach ($products as $product) {
            $productId = (int) ($product['productID'] ?? 0);
            $description = (string) ($product['description'] ?? $product['name'] ?? '');
            $code = (string) ($product['code'] ?? '');
            $parsed = $this->parseDescription($description);

            if ($productId <= 0 || !$parsed || $parsed['reference'] !== $reference) {
                continue; // la búsqueda de Siigo es amplia: ignorar lo que no es de esta referencia
            }

            $color = $parsed['color'];
            $base = ['product_id' => $productId, 'code' => $code, 'color' => $color];

            if (!array_key_exists($color, $jpgByColor)) {
                $jpgByColor[$color] = $this->firstPhotoJpg(ProductPhotoService::BASE_PATH . "/{$reference}/{$color}");
            }

            $jpg = $jpgByColor[$color];
            $origin = 'color';

            if ($jpg === null) {
                if ($rootJpg === false) {
                    $rootJpg = $this->firstPhotoJpg(ProductPhotoService::BASE_PATH . "/{$reference}");
                }

                $jpg = $rootJpg;
                $origin = 'raiz';
            }

            if ($jpg === null) {
                $results[] = $base + [
                    'status' => 'sin_foto_local',
                    'message' => "No hay fotos en products/{$reference}/{$color} ni en products/{$reference}.",
                ];
                continue;
            }

            $results[] = $base + ['origen' => $origin] + $this->processProduct($token, $productId, $jpg, false);
        }

        return [
            'referencia' => $reference,
            'summary' => array_count_values(array_column($results, 'status')),
            'results' => $results,
        ];
    }

    /** $replace = false: si el producto ya tiene foto, la omite. $replace = true: borra la actual y sube la nueva. */
    private function processProduct(string $token, int $productId, string $jpgContents, bool $replace): array
    {
        try {
            $existing = $this->listFiles($token, $productId);

            if ($existing && !$replace) {
                return ['status' => 'omitido', 'message' => 'El producto ya tiene foto en Siigo.'];
            }

            if ($existing) {
                $this->deleteFiles($token, $productId);
            }

            $this->uploadFile($token, $productId, $jpgContents);

            return ['status' => 'subido'];
        } catch (\Throwable $exception) {
            report($exception);

            return ['status' => 'error', 'message' => Str::limit($exception->getMessage(), 200)];
        }
    }

    private function http(string $token): PendingRequest
    {
        return $this->client->servicesAs($token)->timeout(self::TIMEOUT);
    }

    private function catalogPath(): string
    {
        return config('siigo.endpoints.services.catalog');
    }

    /** Busca en Siigo todos los productos (todas las páginas) que coincidan con el texto. */
    private function searchProducts(string $token, string $search): array
    {
        $all = [];
        $seen = [];
        $page = 1;

        do {
            $response = $this->http($token)->get($this->catalogPath() . '/product', [
                'page' => $page,
                'pageSize' => self::PAGE_SIZE,
                'filters' => json_encode(['ProductSearch' => $search, 'Type' => '0,1']),
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Siigo respondió {$response->status()} al buscar productos (página {$page}).");
            }

            $results = $response->json('results') ?? [];
            $new = 0;

            foreach ($results as $item) {
                $id = $item['productID'] ?? null;

                if ($id === null || isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $all[] = $item;
                $new++;
            }

            $page++;
            // Se detiene si la página vino incompleta, vacía o repetida (evita bucles infinitos).
        } while (count($results) >= self::PAGE_SIZE && $new > 0 && $page <= self::MAX_PAGES);

        return $all;
    }

    private function listFiles(string $token, int $productId): array
    {
        $response = $this->http($token)->get($this->catalogPath() . '/FileStorage/File/list', $this->fileParameters($productId));

        if (!$response->successful()) {
            throw new \RuntimeException("Siigo respondió {$response->status()} al listar la foto del producto {$productId}.");
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    private function deleteFiles(string $token, int $productId): void
    {
        // DELETE con los parámetros en el query string, como lo hace Siigo.
        $url = $this->catalogPath() . '/FileStorage/File/list?' . http_build_query($this->fileParameters($productId));

        $response = $this->http($token)->delete($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Siigo respondió {$response->status()} al eliminar la foto del producto {$productId}.");
        }
    }

    private function uploadFile(string $token, int $productId, string $jpgContents): void
    {
        $fileName = (int) (microtime(true) * 1000) . '.jpg';

        $response = $this->http($token)
            ->attach('File', $jpgContents, $fileName, ['Content-Type' => 'image/jpeg'])
            ->post($this->catalogPath() . '/FileStorage/File', [
                'ReferenceType' => 0,
                'ReferenceCode' => $productId,
                'FileName' => $fileName,
                'IsMigrate' => 'true',
                'StorageStrategy' => 1,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Siigo respondió {$response->status()} al subir la foto del producto {$productId}.");
        }
    }

    private function fileParameters(int $productId): array
    {
        return ['referenceCode' => $productId, 'referenceType' => 0, 'storageStrategy' => 1];
    }

    /** Separa la descripción de Siigo por "-" o "*": AGUILA-NEGRO-SEBA-BA-35 => referencia AGUILA, color NEGRO. */
    private function parseDescription(string $description): ?array
    {
        $parts = preg_split('/[-*]/', trim($description));

        if (!$parts || count($parts) < 2) {
            return null;
        }

        // La referencia (posición 0) se toma tal cual; solo el color se normaliza.
        $reference = trim($parts[0]);
        $color = $this->normalizeKey($parts[1]);

        if ($reference === '' || $color === '') {
            return null;
        }

        return ['reference' => $reference, 'color' => $color];
    }

    /** Mayúsculas y espacios simples (sin guiones), para comparar contra los nombres de carpeta. */
    private function normalizeKey(string $value): string
    {
        return preg_replace('/\s+/', ' ', mb_strtoupper(trim($value)));
    }

    /** Primera foto (orden alfabético) directamente en la carpeta, como JPG. Null si no existe o no hay fotos. */
    private function firstPhotoJpg(string $path): ?string
    {
        $disk = Storage::disk(ProductPhotoService::DISK);

        if (!$disk->exists($path)) {
            return null;
        }

        $files = collect($disk->files($path))
            ->filter(fn ($file) => $this->photos->esImagen(basename($file)))
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        $contents = $disk->get($files->first());

        if ($contents === null || $contents === '') {
            return null;
        }

        return $this->images->toJpg($contents);
    }
}

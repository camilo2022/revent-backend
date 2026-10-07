<?php

namespace App\Jobs;

use App\Services\ProductPhotoService;
use App\Services\SiigoInventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

/**
 * Sube a Siigo la foto de los productos SIN foto de UNA referencia.
 * Por producto: foto de products/REFERENCIA/COLOR (la primera) o, si el color no tiene fotos,
 * la primera de products/REFERENCIA. Nunca borra ni reemplaza fotos existentes.
 *
 * Lo despacha CacheProductsSiigoJob con los productos ya acumulados:
 *   $products = [['product_id' => 167836, 'code' => 'AGUINEGR35', 'color' => 'NEGRO'], ...]
 */
class SyncReferencePhotosSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CATALOG_URL = 'https://services.siigo.com/catalog/api';
    private const PHOTO_DISK = ProductPhotoService::DISK;
    private const PHOTO_BASE_PATH = ProductPhotoService::BASE_PATH;

    /** Segundos desde el inicio del job tras los cuales se reencola lo que falte (el timeout es 600). */
    private const TIME_BUDGET = 480;

    /** Sin límite de intentos por "release" del middleware; solo cuentan las excepciones (maxExceptions). */
    public int $tries = 0;
    public int $maxExceptions = 3;
    public int $backoff = 60;
    public int $timeout = 600;

    private int $startedAt = 0;
    private ?string $token = null;

    public function __construct(public string $reference, public array $products)
    {
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(3);
    }

    /** Evita que dos jobs de la misma referencia suban fotos al mismo tiempo (duplicados). */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("siigo-photos:{$this->reference}"))
                ->releaseAfter(30)
                ->expireAfter(700),
        ];
    }

    public function handle(): void
    {
        $this->startedAt = time();
        $this->token = (new SiigoInventoryService())->auth();

        $stats = ['subido' => 0, 'omitido' => 0, 'sin_foto_local' => 0, 'error' => 0, 'reencolado' => 0];
        $jpgByColor = [];
        $rootJpg = false; // false = aún no consultada; null = la raíz no tiene fotos

        foreach ($this->products as $index => $product) {
            // Si se agota el tiempo, el resto pasa a un job nuevo (siempre se procesa al menos un producto).
            if ($index > 0 && time() - $this->startedAt > self::TIME_BUDGET) {
                $remaining = array_slice($this->products, $index);

                static::dispatch($this->reference, $remaining)->delay(now()->addSeconds(5));
                $stats['reencolado'] = count($remaining);
                break;
            }

            try {
                // Confirmar en Siigo que realmente no tiene foto.
                if ($this->siigoProductHasPhoto((int) $product['product_id'])) {
                    $stats['omitido']++;
                    continue;
                }

                $color = (string) $product['color'];

                if (!array_key_exists($color, $jpgByColor)) {
                    $jpgByColor[$color] = $this->firstPhotoAsJpg(self::PHOTO_BASE_PATH . "/{$this->reference}/{$color}");
                }

                $jpg = $jpgByColor[$color];

                if ($jpg === null) {
                    if ($rootJpg === false) {
                        $rootJpg = $this->firstPhotoAsJpg(self::PHOTO_BASE_PATH . "/{$this->reference}");
                    }

                    $jpg = $rootJpg;
                }

                if ($jpg === null) {
                    $stats['sin_foto_local']++;
                    continue;
                }

                $this->siigoUploadPhoto((int) $product['product_id'], $jpg);
                $stats['subido']++;
            } catch (\Throwable $exception) {
                $stats['error']++;
                Log::warning("[SyncReferencePhotosSiigoJob] {$this->reference}: error con el producto {$product['code']} ({$product['product_id']}): " . $exception->getMessage());
            }
        }

        Log::info("[SyncReferencePhotosSiigoJob] Referencia {$this->reference} procesada", $stats);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[SyncReferencePhotosSiigoJob] Falló la referencia {$this->reference}: " . $exception->getMessage());
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
        $response = $send($this->token);

        if ($response->status() === 401) {
            $this->token = (new SiigoInventoryService())->auth();
            $response = $send($this->token);
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

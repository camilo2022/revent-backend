<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\ProductPhoto\BulkUploadRequest;
use App\Http\Requests\Api\Integration\ProductPhoto\DeleteRequest;
use App\Http\Requests\Api\Integration\ProductPhoto\ExplorerDeleteRequest;
use App\Http\Requests\Api\Integration\ProductPhoto\ExplorerFolderRequest;
use App\Http\Requests\Api\Integration\ProductPhoto\ExplorerRequest;
use App\Http\Requests\Api\Integration\ProductPhoto\ExplorerUploadRequest;
use App\Http\Requests\Api\Integration\ProductPhoto\SearchRequest;
use App\Http\Requests\Api\Integration\ProductPhoto\UploadRequest;
use App\Http\Resources\Api\Integration\ProductPhoto\BulkUploadResource;
use App\Http\Resources\Api\Integration\ProductPhoto\ExplorerFolderResource;
use App\Http\Resources\Api\Integration\ProductPhoto\ExplorerResource;
use App\Http\Resources\Api\Integration\ProductPhoto\ExplorerUploadResource;
use App\Http\Resources\Api\Integration\ProductPhoto\PhotoCollection;
use App\Http\Resources\Api\Integration\ProductPhoto\PhotoUploadResource;
use App\Services\Integration\ProductPhotoImageService;
use App\Services\Integration\ProductPhotoSiigoService;
use App\Services\Integration\ProductPhotoZipImportService;
use App\Services\ProductPhotoService;
use App\Support\Integration\LegacyCacheKeys;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fotos de producto. Guarda en el mismo disco y las mismas carpetas (products/{REFERENCIA}/{COLOR}) que las pantallas
 * de Integration, así la web y la API ven las mismas fotos. Cada acción pide su permiso; el token de Siigo solo se
 * pide al subir desde el explorador, porque ahí es Siigo quien recibe la foto.
 */
class ProductPhotoController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    public function __construct(
        private ProductPhotoService $photos,
        private ProductPhotoImageService $images,
        private ProductPhotoZipImportService $zip,
        private ProductPhotoSiigoService $siigo
    ) {
    }

    /** Fotos de la carpeta de una referencia. */
    public function search(SearchRequest $request)
    {
        try {
            $reference = $request->reference();
            $path = ProductPhotoService::BASE_PATH . "/{$reference}";
            $disk = Storage::disk(ProductPhotoService::DISK);

            $files = $disk->exists($path)
                ? collect($disk->files($path))->map(fn ($file) => $this->describe($file))->values()
                : collect();

            return $this->successResponse(
                new PhotoCollection(['referencia' => $reference, 'exists' => $files->isNotEmpty(), 'photos' => $files]),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Sube fotos a la carpeta de una referencia (todas o ninguna). */
    public function upload(UploadRequest $request)
    {
        try {
            $reference = $request->reference();
            $uploaded = $this->storePhotos($request->file('photos'), ProductPhotoService::BASE_PATH . "/{$reference}")['uploaded'];

            Cache::forget(LegacyCacheKeys::inventoryImages($reference));

            return $this->successResponse(
                new PhotoUploadResource(['referencia' => $reference, 'uploaded' => $uploaded]),
                'Fotos subidas correctamente.',
                201
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Elimina una foto de la carpeta de una referencia. */
    public function destroy(DeleteRequest $request)
    {
        try {
            $reference = $request->reference();
            $path = ProductPhotoService::BASE_PATH . "/{$reference}/" . basename((string) $request->input('filename'));
            $disk = Storage::disk(ProductPhotoService::DISK);

            if (!$disk->exists($path)) {
                throw new \DomainException('La foto no existe.');
            }

            $disk->delete($path);
            Cache::forget(LegacyCacheKeys::inventoryImages($reference));

            return $this->successResponse([], 'Foto eliminada correctamente.', 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Importa las fotos de un ZIP (REFERENCIA/imagen.ext o REFERENCIA.ext). El ZIP ya fue revisado por completo. */
    public function bulkUpload(BulkUploadRequest $request)
    {
        try {
            @set_time_limit(600);

            $result = $this->zip->import($request->file('zip')->getRealPath(), $request->plan());

            foreach ($result['references'] as $reference) {
                Cache::forget(LegacyCacheKeys::inventoryImages($reference));
            }

            return $this->successResponse(new BulkUploadResource($result), 'Carga masiva completada.', 201);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Carpetas e imágenes de una ruta del explorador. */
    public function explorer(ExplorerRequest $request)
    {
        try {
            $data = $this->photos->listar($request->folder());

            if ($data === null) {
                throw new \DomainException('La carpeta no existe.');
            }

            return $this->successResponse(new ExplorerResource($data), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Crea un producto (en products) o una carpeta de color (dentro de un producto). */
    public function explorerFolder(ExplorerFolderRequest $request)
    {
        try {
            $result = $this->photos->crearCarpeta($request->folder(), (string) $request->input('name'));

            if (!$result['success']) {
                throw new \DomainException($result['message']);
            }

            return $this->successResponse(new ExplorerFolderResource($result), $result['message'], 201);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * Sube fotos a la carpeta de un producto o de un color (todas o ninguna). Si la carpeta es de un color, la primera foto
     * reemplaza la de los productos de ese color en Siigo; si es la del producto, completa los que no tienen foto.
     */
    public function explorerUpload(ExplorerUploadRequest $request)
    {
        try {
            @set_time_limit(600);

            $info = $request->folder();

            if (!Storage::disk(ProductPhotoService::DISK)->exists($info['relative'])) {
                throw new \DomainException('La carpeta no existe.');
            }

            $stored = $this->storePhotos($request->file('photos'), $info['relative']);
            $parts = array_values(array_filter(explode('/', $info['path']), fn ($part) => $part !== ''));
            $token = trim((string) $request->input('token'));

            $response = ['path' => $info['path'], 'uploaded' => $stored['uploaded']];

            if (count($parts) === 2) {
                $response['siigo'] = $this->siigo->replaceColorPhoto($token, $parts[0], $parts[1], $stored['first']);
            } elseif (count($parts) === 1) {
                $response['siigo'] = $this->siigo->completeReferencePhotos($token, $parts[0]);
            }

            Cache::forget(LegacyCacheKeys::inventoryImages($parts[0]));

            return $this->successResponse(new ExplorerUploadResource($response), 'Fotos subidas correctamente.', 201);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Elimina una foto de la carpeta de un producto o de un color. */
    public function explorerDelete(ExplorerDeleteRequest $request)
    {
        try {
            $info = $request->folder();
            $path = "{$info['relative']}/" . basename((string) $request->input('filename'));
            $disk = Storage::disk(ProductPhotoService::DISK);

            if (!$disk->exists($path)) {
                throw new \DomainException('La foto no existe.');
            }

            $disk->delete($path);
            Cache::forget(LegacyCacheKeys::inventoryImages(explode('/', $info['path'])[0]));

            return $this->successResponse([], 'Foto eliminada correctamente.', 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * Convierte a WebP y guarda cada foto en la carpeta. Si una falla se borran las que ya se guardaron.
     *
     * @return array{uploaded: array, first: ?string} `first` es el contenido WebP de la primera foto.
     */
    private function storePhotos(array $photos, string $directory): array
    {
        $disk = Storage::disk(ProductPhotoService::DISK);
        $uploaded = [];
        $saved = [];
        $first = null;

        try {
            foreach ($photos as $photo) {
                $contents = file_get_contents($photo->getRealPath());

                if ($contents === false) {
                    throw new \DomainException('No se pudo leer una de las imágenes.');
                }

                $webp = $this->images->toWebp($contents);
                $path = "{$directory}/" . Str::uuid() . '.webp';

                if (!$disk->put($path, $webp)) {
                    throw new \RuntimeException('No se pudo guardar una de las imágenes convertidas.');
                }

                $first ??= $webp;
                $saved[] = $path;
                $uploaded[] = ['name' => basename($path), 'url' => $disk->url($path), 'size' => strlen($webp)];
            }
        } catch (\Throwable $exception) {
            foreach ($saved as $path) {
                $disk->delete($path);
            }

            throw $exception;
        }

        return ['uploaded' => $uploaded, 'first' => $first];
    }

    private function describe(string $file): array
    {
        $disk = Storage::disk(ProductPhotoService::DISK);

        return ['name' => basename($file), 'url' => $disk->url($file), 'size' => $disk->size($file)];
    }
}

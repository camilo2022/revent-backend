<?php

namespace App\Services\Integration;

use App\Services\ProductPhotoService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Carga masiva de fotos desde un ZIP: primero se valida toda la estructura (inspect) y después se guarda (import).
 * Copiado de Integration\PhotoProductSiigoController::product_photo_bulk_upload (misma estructura, límites y mensajes).
 *
 * Modos: `folders` = REFERENCIA/imagen.ext; `filenames` = REFERENCIA.ext en la raíz.
 */
class ProductPhotoZipImportService
{
    public const MODE_FOLDERS = 'folders';

    public const MODE_FILENAMES = 'filenames';

    public const MAX_IMAGE_SIZE_BYTES = 10485760; // 10 MB por imagen

    public const MAX_TOTAL_IMAGE_BYTES = 524288000; // 500 MB descomprimidos

    public const MAX_ZIP_ENTRIES = 5000;

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const MIMES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
    ];

    public function __construct(private ProductPhotoImageService $images)
    {
    }

    /**
     * Revisa el ZIP sin guardar nada.
     *
     * @return array{entries: array<int, array>, references: string[], empty_folders: string[], errors: string[]}
     */
    public function inspect(string $zipPath, string $mode): array
    {
        $plan = ['entries' => [], 'references' => [], 'empty_folders' => [], 'errors' => []];

        if (!class_exists(ZipArchive::class)) {
            $plan['errors'][] = 'El servidor no tiene habilitada la extensión PHP ZipArchive.';

            return $plan;
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            $plan['errors'][] = 'No se pudo abrir el ZIP. Comprueba que no esté dañado o protegido con contraseña.';

            return $plan;
        }

        try {
            if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
                $plan['errors'][] = 'El ZIP contiene demasiadas entradas. El máximo permitido es ' . self::MAX_ZIP_ENTRIES . '.';

                return $plan;
            }

            $references = [];
            $emptyFolders = [];
            $totalBytes = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);

                if (!$stat || !isset($stat['name'])) {
                    $plan['errors'][] = 'No se pudo leer una entrada del ZIP.';
                    continue;
                }

                $entryName = str_replace('\\', '/', $stat['name']);
                $isDirectory = str_ends_with($entryName, '/');
                $trimmed = trim($entryName, '/');

                if ($trimmed === '') {
                    continue;
                }

                $parts = explode('/', $trimmed);
                $reference = null;
                $filename = null;

                if ($mode === self::MODE_FOLDERS) {
                    if (count($parts) === 1) {
                        if ($isDirectory) {
                            if (!$this->images->isValidBulkReference($parts[0])) {
                                $plan['errors'][] = "Nombre de carpeta inválido: \"{$parts[0]}\". Usa solo MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.";
                            } else {
                                $emptyFolders[$parts[0]] = true;
                            }
                        } else {
                            $plan['errors'][] = "No se permiten archivos en la raíz del ZIP: {$entryName}";
                        }
                        continue;
                    }

                    if (count($parts) !== 2) {
                        $plan['errors'][] = "No se permiten subcarpetas: {$entryName}";
                        continue;
                    }

                    [$reference, $filename] = $parts;

                    if (!$this->images->isValidBulkReference($reference)) {
                        $plan['errors'][] = "Nombre de carpeta inválido: \"{$reference}\". Usa solo MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.";
                        continue;
                    }

                    if ($isDirectory) {
                        $plan['errors'][] = "No se permiten subcarpetas: {$entryName}";
                        continue;
                    }
                } else {
                    if ($isDirectory) {
                        $plan['errors'][] = "El ZIP de imágenes por nombre no debe contener carpetas: {$entryName}";
                        continue;
                    }

                    if (count($parts) !== 1) {
                        $plan['errors'][] = "En el modo por nombre, todas las imágenes deben estar en la raíz: {$entryName}";
                        continue;
                    }

                    $filename = $parts[0];
                    $reference = pathinfo($filename, PATHINFO_FILENAME);

                    if (!$this->images->isValidBulkReference($reference)) {
                        $plan['errors'][] = "Nombre de imagen inválido: \"{$filename}\". El nombre sin extensión debe ser la referencia en MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.";
                        continue;
                    }
                }

                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (!in_array($extension, self::EXTENSIONS, true)) {
                    $plan['errors'][] = "Archivo no permitido: {$entryName}. Solo se aceptan JPG, JPEG, PNG y WEBP.";
                    continue;
                }

                $size = (int) ($stat['size'] ?? 0);

                if ($size <= 0 || $size > self::MAX_IMAGE_SIZE_BYTES) {
                    $plan['errors'][] = "La imagen {$entryName} está vacía o supera el límite de 10 MB.";
                    continue;
                }

                $totalBytes += $size;

                if ($totalBytes > self::MAX_TOTAL_IMAGE_BYTES) {
                    $plan['errors'][] = 'El tamaño total descomprimido de las imágenes supera los 500 MB permitidos.';
                    break;
                }

                $references[$reference] = true;
                unset($emptyFolders[$reference]);
                $plan['entries'][] = [
                    'name' => $entryName,
                    'reference' => $reference,
                    'extension' => $extension,
                    'size' => $size,
                ];
            }

            if (!$plan['entries'] && !$plan['errors']) {
                $plan['errors'][] = 'El ZIP no contiene imágenes válidas para importar.';
            }

            $plan['references'] = array_keys($references);
            $plan['empty_folders'] = array_keys($emptyFolders);
            $plan['errors'] = array_values(array_unique($plan['errors']));

            return $plan;
        } finally {
            $zip->close();
        }
    }

    /**
     * Guarda las imágenes de un plan ya validado. Si una falla, borra todo lo guardado en esta importación.
     *
     * @return array{references_processed: int, images_uploaded: int, empty_folders: int, references: string[]}
     */
    public function import(string $zipPath, array $plan): array
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \DomainException('No se pudo abrir el ZIP. Comprueba que no esté dañado o protegido con contraseña.');
        }

        $disk = Storage::disk(ProductPhotoService::DISK);
        $saved = [];

        try {
            foreach ($plan['entries'] as $entry) {
                $stream = $zip->getStream($entry['name']);

                if ($stream === false) {
                    throw new \RuntimeException('No se pudo leer el archivo ' . $entry['name']);
                }

                try {
                    $contents = stream_get_contents($stream);
                } finally {
                    fclose($stream);
                }

                if ($contents === false || strlen($contents) !== $entry['size']) {
                    throw new \RuntimeException('No se pudo leer completamente el archivo ' . $entry['name']);
                }

                // Comprobar que el contenido sea una imagen real, no solo un archivo con extensión de imagen.
                $info = @getimagesizefromstring($contents);

                if (!$info || !in_array($info['mime'] ?? '', self::MIMES[$entry['extension']], true)) {
                    throw new \DomainException('El archivo ' . $entry['name'] . ' no contiene una imagen válida para su extensión.');
                }

                $path = ProductPhotoService::BASE_PATH . '/' . $entry['reference'] . '/' . Str::uuid() . '.webp';

                if (!$disk->put($path, $this->images->toWebp($contents))) {
                    throw new \RuntimeException('No se pudo guardar la imagen convertida ' . $entry['name']);
                }

                $saved[] = $path;
            }
        } catch (\Throwable $exception) {
            // Evita dejar una carga parcial si falla una imagen a mitad del proceso.
            foreach ($saved as $path) {
                $disk->delete($path);
            }

            throw $exception;
        } finally {
            $zip->close();
        }

        return [
            'references_processed' => count($plan['references']),
            'images_uploaded' => count($saved),
            'empty_folders' => count($plan['empty_folders']),
            'references' => $plan['references'],
        ];
    }
}

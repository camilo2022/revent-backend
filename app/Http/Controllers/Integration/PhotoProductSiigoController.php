<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use ZipArchive;
use Throwable;

class PhotoProductSiigoController extends Controller
{
    private const DISK = 'public';
    private const BASE_PATH = 'products';
    private const ALLOWED_USER_IDS = [597];
    private const MAX_ZIP_SIZE_KB = 512000; // 100 MB
    private const MAX_IMAGE_SIZE_BYTES = 10485760; // 10 MB por imagen
    private const MAX_TOTAL_IMAGE_BYTES = 524288000; // 500 MB descomprimidos
    private const MAX_ZIP_ENTRIES = 5000;

    public function product_photo()
    {
        return view('integration.photo_product');
    }

    public function product_photo_search(Request $request)
    {
        $request->validate([
            'referencia' => 'required|string',
        ]);

        $referencia = $this->sanitize_referencia($request->input('referencia'));
        $path = self::BASE_PATH . "/{$referencia}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            return response()->json([
                'exists' => false,
                'referencia' => $referencia,
                'photos' => [],
            ]);
        }

        $files = collect(Storage::disk(self::DISK)->files($path))
            ->map(fn ($file) => [
                'name' => basename($file),
                'url' => Storage::disk(self::DISK)->url($file),
                'size' => Storage::disk(self::DISK)->size($file),
            ])
            ->values();

        return response()->json([
            'exists' => $files->isNotEmpty(),
            'referencia' => $referencia,
            'photos' => $files,
        ]);
    }

    public function product_photo_upload(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'referencia' => 'required|string',
            'photos' => 'required|array|min:1',
            'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $usuario = $this->validar_usuario_permitido($request->input('token'));

        if (!$usuario['success']) {
            return response()->json([
                'success' => false,
                'error' => $usuario['message'],
            ], 401);
        }

        $referencia = $this->sanitize_referencia($request->input('referencia'));
        $path = self::BASE_PATH . "/{$referencia}";

        $uploaded = [];

        foreach ($request->file('photos') as $photo) {
            $originalContents = file_get_contents($photo->getRealPath());

            if ($originalContents === false) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo leer una de las imágenes.',
                ], 422);
            }

            $webpContents = $this->convertir_webp($originalContents);
            $filename = Str::uuid() . '.webp';
            $filePath = "{$path}/{$filename}";

            if (!Storage::disk(self::DISK)->put($filePath, $webpContents)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo guardar una de las imágenes convertidas.',
                ], 500);
            }

            $uploaded[] = [
                'name' => $filename,
                'url' => Storage::disk(self::DISK)->url($filePath),
                'size' => strlen($webpContents),
            ];
        }

        return response()->json([
            'success' => true,
            'referencia' => $referencia,
            'uploaded' => $uploaded,
        ]);
    }

    /**
     * Importa las imágenes de un ZIP con estructura REFERENCIA/imagen.ext.
     * La carpeta de referencia se crea implícitamente al guardar el primer archivo.
     * Las carpetas vacías son válidas y se omiten porque no contienen imágenes que guardar.
     */
    public function product_photo_bulk_upload(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'zip' => 'required|file|mimes:zip|max:' . self::MAX_ZIP_SIZE_KB,
            'zip_mode' => 'required|in:folders,filenames',
        ]);

        // Validar el token y el ID del usuario antes de procesar/guardar imágenes.
        $usuario = $this->validar_usuario_permitido($request->input('token'));

        if (!$usuario['success']) {
            return response()->json([
                'success' => false,
                'error' => $usuario['message'],
            ], 401);
        }

        if (!class_exists(ZipArchive::class)) {
            return response()->json([
                'success' => false,
                'error' => 'El servidor no tiene habilitada la extensión PHP ZipArchive.',
            ], 500);
        }

        $zip = new ZipArchive();
        $opened = $zip->open($request->file('zip')->getRealPath());

        if ($opened !== true) {
            return response()->json([
                'success' => false,
                'error' => 'No se pudo abrir el ZIP. Comprueba que no esté dañado o protegido con contraseña.',
            ], 422);
        }

        $zipMode = $request->input('zip_mode');
        $savedPaths = [];
        $errors = [];
        $references = [];
        $emptyFolders = [];
        $imageCount = 0;
        $totalBytes = 0;

        try {
            if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
                return response()->json([
                    'success' => false,
                    'error' => 'El ZIP contiene demasiadas entradas. El máximo permitido es ' . self::MAX_ZIP_ENTRIES . '.',
                ], 422);
            }

            // Primera pasada: validar toda la estructura antes de guardar cualquier archivo.
            $entriesToSave = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (!$stat || !isset($stat['name'])) {
                    $errors[] = 'No se pudo leer una entrada del ZIP.';
                    continue;
                }

                $entryName = str_replace('\\', '/', $stat['name']);
                $isDirectory = str_ends_with($entryName, '/');
                $trimmedName = trim($entryName, '/');

                if ($trimmedName === '') {
                    continue;
                }

                $parts = explode('/', $trimmedName);
                $reference = null;
                $filename = null;

                if ($zipMode === 'folders') {
                    if (count($parts) === 1) {
                        if ($isDirectory) {
                            $folderReference = $parts[0];
                            if (!$this->validar_nombre_referencia_masiva($folderReference)) {
                                $errors[] = "Nombre de carpeta inválido: \"{$folderReference}\". Usa solo MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.";
                            } else {
                                $emptyFolders[$folderReference] = true;
                            }
                        } else {
                            $errors[] = "No se permiten archivos en la raíz del ZIP: {$entryName}";
                        }
                        continue;
                    }

                    if (count($parts) !== 2) {
                        $errors[] = "No se permiten subcarpetas: {$entryName}";
                        continue;
                    }

                    $reference = $parts[0];
                    $filename = $parts[1];

                    if (!$this->validar_nombre_referencia_masiva($reference)) {
                        $errors[] = "Nombre de carpeta inválido: \"{$reference}\". Usa solo MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.";
                        continue;
                    }

                    if ($isDirectory) {
                        $errors[] = "No se permiten subcarpetas: {$entryName}";
                        continue;
                    }
                } else {
                    if ($isDirectory) {
                        $errors[] = "El ZIP de imágenes por nombre no debe contener carpetas: {$entryName}";
                        continue;
                    }

                    if (count($parts) !== 1) {
                        $errors[] = "En el modo por nombre, todas las imágenes deben estar en la raíz: {$entryName}";
                        continue;
                    }

                    $filename = $parts[0];
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $reference = pathinfo($filename, PATHINFO_FILENAME);

                    if (!$this->validar_nombre_referencia_masiva($reference)) {
                        $errors[] = "Nombre de imagen inválido: \"{$filename}\". El nombre sin extensión debe ser la referencia en MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.";
                        continue;
                    }
                }

                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $errors[] = "Archivo no permitido: {$entryName}. Solo se aceptan JPG, JPEG, PNG y WEBP.";
                    continue;
                }

                $size = (int) ($stat['size'] ?? 0);
                if ($size <= 0 || $size > self::MAX_IMAGE_SIZE_BYTES) {
                    $errors[] = "La imagen {$entryName} está vacía o supera el límite de 10 MB.";
                    continue;
                }

                $totalBytes += $size;
                if ($totalBytes > self::MAX_TOTAL_IMAGE_BYTES) {
                    $errors[] = 'El tamaño total descomprimido de las imágenes supera los 500 MB permitidos.';
                    break;
                }

                $references[$reference] = true;
                unset($emptyFolders[$reference]);
                $entriesToSave[] = [
                    'index' => $i,
                    'name' => $entryName,
                    'reference' => $reference,
                    'extension' => $extension,
                    'size' => $size,
                ];
            }

            if (!$entriesToSave) {
                $errors[] = 'El ZIP no contiene imágenes válidas para importar.';
            }

            if ($errors) {
                return response()->json([
                    'success' => false,
                    'error' => 'El ZIP contiene problemas. No se guardó ninguna imagen.',
                    'errors' => array_values(array_unique($errors)),
                ], 422);
            }

            foreach ($entriesToSave as $entry) {
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
                $imageInfo = @getimagesizefromstring($contents);
                $allowedMimes = [
                    'jpg' => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'png' => ['image/png'],
                    'webp' => ['image/webp'],
                ];

                if (!$imageInfo || !in_array($imageInfo['mime'] ?? '', $allowedMimes[$entry['extension']], true)) {
                    throw new \RuntimeException('El archivo ' . $entry['name'] . ' no contiene una imagen válida para su extensión.');
                }

                $reference = $entry['reference'];
                $directory = self::BASE_PATH . '/' . $reference;
                $filename = Str::uuid() . '.webp';
                $path = $directory . '/' . $filename;
                $webpContents = $this->convertir_webp($contents);

                if (!Storage::disk(self::DISK)->put($path, $webpContents)) {
                    throw new \RuntimeException('No se pudo guardar la imagen convertida ' . $entry['name']);
                }

                $savedPaths[] = $path;
                $imageCount++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Carga masiva completada.',
                'references_processed' => count($references),
                'images_uploaded' => $imageCount,
                'empty_folders' => count($emptyFolders),
                'errors' => [],
            ]);
        } catch (Throwable $exception) {
            // Evita dejar una carga parcial si falla una imagen a mitad del proceso.
            foreach ($savedPaths as $savedPath) {
                Storage::disk(self::DISK)->delete($savedPath);
            }

            report($exception);

            return response()->json([
                'success' => false,
                'error' => 'Ocurrió un error al guardar las imágenes. No se conservaron las imágenes de esta importación.',
            ], 500);
        } finally {
            $zip->close();
        }
    }

    public function product_photo_delete(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'referencia' => 'required|string',
            'filename' => 'required|string',
        ]);

        $usuario = $this->validar_usuario_permitido($request->input('token'));

        if (!$usuario['success']) {
            return response()->json([
                'success' => false,
                'error' => $usuario['message'],
            ], 401);
        }

        $referencia = $this->sanitize_referencia($request->input('referencia'));
        $filename = basename($request->input('filename'));
        $path = self::BASE_PATH . "/{$referencia}/{$filename}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            return response()->json([
                'success' => false,
                'error' => 'La foto no existe',
            ], 404);
        }

        Storage::disk(self::DISK)->delete($path);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Convierte una imagen válida a WebP y devuelve sus bytes.
     * Conserva la transparencia cuando el formato de origen la soporta.
     */
    private function convertir_webp(string $contents): string
    {
        $manager = new ImageManager(new Driver());

         return $manager
            ->decodeBinary($contents)
            ->encodeUsingFormat(Format::WEBP, quality: 80)
            ->toString();
    }

    private function sanitize_referencia(string $referencia): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '-', trim($referencia)));
    }

    private function validar_nombre_referencia_masiva(string $referencia): bool
    {
        return preg_match('/^[A-ZÑ0-9_-]+$/u', $referencia) === 1;
    }

    private function validar_usuario_permitido(string $token): array
    {
        $usuario = $this->obtener_datos_usuario($token);

        if (!$usuario['success']) {
            return [
                'success' => false,
                'message' => $usuario['message'],
            ];
        }

        if (!in_array((int) $usuario['data']['id'], self::ALLOWED_USER_IDS, true)) {
            return [
                'success' => false,
                'message' => 'Usuario no autorizado',
            ];
        }

        return [
            'success' => true,
            'data' => $usuario['data'],
        ];
    }

    private function obtener_datos_usuario(string $token): array
    {
        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->get('https://services.siigo.com/cross/globalstate/api/v1/Settings/LoadSettings');

        if (!$response->successful()) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'Error de autenticación',
            ];
        }

        $data = $response->json();

        if (!isset($data['userID'], $data['userName'], $data['UserOptions']['userPrincipalName'])) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'La respuesta de Siigo no contiene los datos esperados del usuario.',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'id' => (int) $data['userID'],
                'user' => $data['userName'],
                'name' => $data['UserOptions']['userPrincipalName'],
            ],
            'message' => 'Usuario encontrado exitosamente',
        ];
    }
}

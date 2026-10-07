<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\ProductPhotoService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
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
    private const DISK = ProductPhotoService::DISK;
    private const BASE_PATH = ProductPhotoService::BASE_PATH;
    private const ALLOWED_USER_IDS = [597];
    private const MAX_ZIP_SIZE_KB = 512000; // 500 MB
    private const MAX_IMAGE_SIZE_BYTES = 10485760; // 10 MB por imagen
    private const MAX_TOTAL_IMAGE_BYTES = 524288000; // 500 MB descomprimidos
    private const MAX_ZIP_ENTRIES = 5000;

    private const SIIGO_CATALOG_URL = 'https://services.siigo.com/catalog/api';
    private const SIIGO_PAGE_SIZE = 100;
    private const SIIGO_MAX_PAGES = 1; // tope de seguridad para la paginación
    private const SIIGO_TIMEOUT = 60;

    public function __construct(private ProductPhotoService $photos)
    {
    }

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

    // ------------------------------------------------------------------
    // Explorador de carpetas: products / {REFERENCIA} / {CODIGO_COLOR}
    // ------------------------------------------------------------------

    /** Lista carpetas e imágenes de una ruta. Solo lectura, no requiere token. */
    public function product_photo_explorer(Request $request)
    {
        $request->validate([
            'path' => 'nullable|string|max:200',
        ]);

        $info = $this->photos->resolverRuta($request->input('path'));

        if (!$info) {
            return response()->json([
                'success' => false,
                'error' => 'Ruta no válida.',
            ], 422);
        }

        $data = $this->photos->listar($info);

        if ($data === null) {
            return response()->json([
                'success' => false,
                'error' => 'La carpeta no existe.',
            ], 404);
        }

        return response()->json(['success' => true] + $data);
    }

    /** Crea un producto (en products) o una carpeta de color (dentro de un producto). */
    public function product_photo_explorer_create_folder(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'path' => 'nullable|string|max:200',
            'name' => 'required|string|max:100',
        ]);

        if ($denied = $this->autorizar($request)) {
            return $denied;
        }

        $info = $this->photos->resolverRuta($request->input('path'));

        if (!$info) {
            return response()->json(['success' => false, 'error' => 'Ruta no válida.'], 422);
        }

        $result = $this->photos->crearCarpeta($info, $request->input('name'));

        if (!$result['success']) {
            return response()->json(['success' => false, 'error' => $result['message']], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'path' => $result['path'],
        ]);
    }

    /**
     * Sube fotos a la carpeta actual (producto o color). No se permite en la raíz.
     * Si la carpeta es de un color (REFERENCIA/COLOR), la primera foto subida
     * reemplaza la foto de los productos de ese color en Siigo.
     */
    public function product_photo_explorer_upload(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'path' => 'required|string|max:200',
            'photos' => 'required|array|min:1',
            'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        if ($denied = $this->autorizar($request)) {
            return $denied;
        }

        $info = $this->photos->resolverRuta($request->input('path'));

        if (!$info || $info['level'] === 0) {
            return response()->json([
                'success' => false,
                'error' => 'Abre la carpeta de un producto o de un color para subir fotos.',
            ], 422);
        }

        if (!Storage::disk(self::DISK)->exists($info['relative'])) {
            return response()->json(['success' => false, 'error' => 'La carpeta no existe.'], 404);
        }

        $uploaded = [];
        $savedPaths = [];
        $firstWebp = null;

        try {
            foreach ($request->file('photos') as $photo) {
                $contents = file_get_contents($photo->getRealPath());

                if ($contents === false) {
                    throw new \RuntimeException('No se pudo leer una de las imágenes.');
                }

                $webpContents = $this->convertir_webp($contents);
                $filename = Str::uuid() . '.webp';
                $filePath = "{$info['relative']}/{$filename}";

                if (!Storage::disk(self::DISK)->put($filePath, $webpContents)) {
                    throw new \RuntimeException('No se pudo guardar una de las imágenes convertidas.');
                }

                $firstWebp ??= $webpContents;
                $savedPaths[] = $filePath;
                $uploaded[] = [
                    'name' => $filename,
                    'url' => Storage::disk(self::DISK)->url($filePath),
                    'size' => strlen($webpContents),
                ];
            }
        } catch (Throwable $exception) {
            foreach ($savedPaths as $savedPath) {
                Storage::disk(self::DISK)->delete($savedPath);
            }

            report($exception);

            return response()->json([
                'success' => false,
                'error' => 'No se pudieron procesar las fotos. No se guardó ninguna.',
            ], 500);
        }

        $response = [
            'success' => true,
            'path' => $info['path'],
            'uploaded' => $uploaded,
        ];

        $partes = $this->partes_ruta($info);

        if (count($partes) === 2 && $firstWebp !== null) {
            // Carpeta de color: reemplaza la foto de los productos de ese color en Siigo.
            $response['siigo'] = $this->reemplazar_foto_siigo_color(
                $request->input('token'),
                $partes[0],
                $partes[1],
                $firstWebp
            );
        } elseif (count($partes) === 1) {
            // Carpeta raíz de la referencia: completa solo los productos que no tienen foto en Siigo.
            $response['siigo'] = $this->completar_fotos_siigo_referencia($request->input('token'), $partes[0]);
        }

        return response()->json($response);
    }

    /** Elimina una foto de la carpeta actual (producto o color). */
    public function product_photo_explorer_delete(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'path' => 'required|string|max:200',
            'filename' => 'required|string',
        ]);

        if ($denied = $this->autorizar($request)) {
            return $denied;
        }

        $info = $this->photos->resolverRuta($request->input('path'));
        $filename = basename($request->input('filename'));

        if (!$info || $info['level'] === 0 || !$this->photos->esImagen($filename)) {
            return response()->json(['success' => false, 'error' => 'Solicitud no válida.'], 422);
        }

        $path = "{$info['relative']}/{$filename}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            return response()->json(['success' => false, 'error' => 'La foto no existe'], 404);
        }

        Storage::disk(self::DISK)->delete($path);

        return response()->json(['success' => true]);
    }

    /**
     * Sincroniza con Siigo las fotos de una referencia.
     * Recorre los productos de Siigo, y a los que NO tienen foto les envía la primera
     * foto (JPG) de la carpeta de su color. Nunca borra ni reemplaza fotos existentes.
     */
    public function product_photo_siigo_sync(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'referencia' => 'required|string|max:100',
        ]);

        if ($denied = $this->autorizar($request)) {
            return $denied;
        }

        $token = $request->input('token');
        // La referencia debe coincidir tal cual con la carpeta y con la posición 0 de la descripción.
        $referencia = trim($request->input('referencia'));

        if (!Storage::disk(self::DISK)->exists(self::BASE_PATH . "/{$referencia}")) {
            return response()->json([
                'success' => false,
                'error' => "La carpeta de la referencia \"{$referencia}\" no existe.",
            ], 404);
        }

        @set_time_limit(600);

        try {
            $data = $this->sincronizar_referencia_siigo($token, $referencia);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'error' => 'No se pudo consultar los productos en Siigo: ' . Str::limit($exception->getMessage(), 200),
            ], 502);
        }

        return response()->json(['success' => true] + $data);
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

    private function convertir_webp(string $contents): string
    {
        $manager = new ImageManager(new Driver());

        $image = $manager->decodeBinary($contents);

        // Redimensionar proporcionalmente, sin ampliar imágenes pequeñas.
        $image->scaleDown(width: 1000, height: 1000);

        return $image->encodeUsingFormat(Format::WEBP, quality: 80)
            ->toString();
    }

    private function sanitize_referencia(string $referencia): string
    {
        $referencia = trim($referencia);

        // Unifica la Ñ descompuesta (N + acento combinado, común en Mac) en un solo carácter.
        if (class_exists(\Normalizer::class)) {
            $referencia = \Normalizer::normalize($referencia, \Normalizer::FORM_C) ?: $referencia;
        }

        // Con el modificador /u la Ñ cuenta como un carácter. Devuelve null si el texto no es UTF-8 válido.
        $limpia = preg_replace('/[^A-Za-zÑñ0-9\-_]/u', '-', $referencia);

        return mb_strtoupper($limpia ?? '');
    }

    private function validar_nombre_referencia_masiva(string $referencia): bool
    {
        return preg_match('/^[A-ZÑ0-9_-]+$/u', $referencia) === 1;
    }

    /** Devuelve una respuesta 401 si el token no es válido/autorizado; null si todo bien. */
    private function autorizar(Request $request): ?JsonResponse
    {
        $usuario = $this->validar_usuario_permitido($request->input('token'));

        if (!$usuario['success']) {
            return response()->json([
                'success' => false,
                'error' => $usuario['message'],
            ], 401);
        }

        return null;
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

    // ------------------------------------------------------------------
    // Envío de fotos a Siigo
    // ------------------------------------------------------------------

    /**
     * Reemplaza la foto de todos los productos de Siigo de una referencia + color.
     * Se usa al subir fotos a una carpeta de color. Nunca lanza excepciones: devuelve
     * el resultado para que un fallo de Siigo no afecte la carga local.
     */
    private function reemplazar_foto_siigo_color(string $token, string $referencia, string $color, string $webpContents): array
    {
        try {
            // Referencia tal cual (nombre de la carpeta); solo el color se normaliza.
            $color = $this->normalizar_clave($color);
            $jpg = $this->convertir_jpg($webpContents);

            $results = [];

            foreach ($this->siigo_buscar_productos($token, $referencia) as $product) {
                $productId = (int) ($product['productID'] ?? 0);
                $description = (string) ($product['description'] ?? $product['name'] ?? '');
                $parsed = $this->parsear_descripcion_producto($description);

                if ($productId <= 0 || !$parsed
                    || $parsed['reference'] !== $referencia
                    || $parsed['color'] !== $color) {
                    continue;
                }

                $results[] = [
                    'product_id' => $productId,
                    'code' => (string) ($product['code'] ?? ''),
                ] + $this->procesar_producto_siigo($token, $productId, $jpg, true);
            }

            $summary = array_count_values(array_column($results, 'status'));

            return [
                'success' => !isset($summary['error']),
                'products_found' => count($results),
                'summary' => $summary,
                'results' => $results,
                'message' => $results
                    ? null
                    : "No se encontraron productos en Siigo para {$referencia} / {$color}.",
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'error' => 'No se pudo enviar la foto a Siigo: ' . Str::limit($exception->getMessage(), 200),
            ];
        }
    }

    /**
     * Envía una foto a un producto de Siigo.
     * $reemplazar = false: si ya tiene foto, la omite.
     * $reemplazar = true: borra la foto actual y sube la nueva.
     */
    private function procesar_producto_siigo(string $token, int $productId, string $jpgContents, bool $reemplazar): array
    {
        try {
            $existing = $this->siigo_listar_archivos($token, $productId);

            if ($existing && !$reemplazar) {
                return [
                    'status' => 'omitido',
                    'message' => 'El producto ya tiene foto en Siigo.',
                ];
            }

            if ($existing) {
                $this->siigo_eliminar_archivos($token, $productId);
            }

            $this->siigo_subir_archivo($token, $productId, $jpgContents);

            return ['status' => 'subido'];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'status' => 'error',
                'message' => Str::limit($exception->getMessage(), 200),
            ];
        }
    }

    private function siigo_http(string $token): PendingRequest
    {
        return Http::withHeaders(['Authorization' => $token])
            ->acceptJson()
            ->timeout(self::SIIGO_TIMEOUT);
    }

    /** Busca en Siigo todos los productos (todas las páginas) que coincidan con el texto. */
    private function siigo_buscar_productos(string $token, string $search): array
    {
        $all = [];
        $seen = [];
        $page = 1;

        do {
            $response = $this->siigo_http($token)->get(self::SIIGO_CATALOG_URL . '/product', [
                'page' => $page,
                'pageSize' => self::SIIGO_PAGE_SIZE,
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
        } while (count($results) >= self::SIIGO_PAGE_SIZE && $new > 0 && $page <= self::SIIGO_MAX_PAGES);

        return $all;
    }

    /** Devuelve los archivos (fotos) que tiene un producto en Siigo. */
    private function siigo_listar_archivos(string $token, int $productId): array
    {
        $response = $this->siigo_http($token)->get(
            self::SIIGO_CATALOG_URL . '/FileStorage/File/list',
            $this->siigo_parametros_archivo($productId)
        );

        if (!$response->successful()) {
            throw new \RuntimeException("Siigo respondió {$response->status()} al listar la foto del producto {$productId}.");
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    private function siigo_eliminar_archivos(string $token, int $productId): void
    {
        // DELETE con los parámetros en el query string, como lo hace Siigo.
        $url = self::SIIGO_CATALOG_URL . '/FileStorage/File/list?'
            . http_build_query($this->siigo_parametros_archivo($productId));

        $response = $this->siigo_http($token)->delete($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Siigo respondió {$response->status()} al eliminar la foto del producto {$productId}.");
        }
    }

    private function siigo_subir_archivo(string $token, int $productId, string $jpgContents): void
    {
        $fileName = (int) (microtime(true) * 1000) . '.jpg';

        $response = $this->siigo_http($token)
            ->attach('File', $jpgContents, $fileName, ['Content-Type' => 'image/jpeg'])
            ->post(self::SIIGO_CATALOG_URL . '/FileStorage/File', [
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

    private function siigo_parametros_archivo(int $productId): array
    {
        return [
            'referenceCode' => $productId,
            'referenceType' => 0,
            'storageStrategy' => 1,
        ];
    }

    /**
     * Separa la descripción de Siigo por "-" o "*".
     * AGUILA-NEGRO-SEBA-BA-35 => referencia AGUILA, color NEGRO.
     */
    private function parsear_descripcion_producto(string $description): ?array
    {
        $parts = preg_split('/[-*]/', trim($description));

        if (!$parts || count($parts) < 2) {
            return null;
        }

        // La referencia (posición 0) se toma tal cual; solo el color se normaliza.
        $reference = trim($parts[0]);
        $color = $this->normalizar_clave($parts[1]);

        if ($reference === '' || $color === '') {
            return null;
        }

        return ['reference' => $reference, 'color' => $color];
    }

    /** Mayúsculas y espacios como guion, para comparar contra los nombres de carpeta. */
    private function normalizar_clave(string $value): string
    {
        return preg_replace('/\s+/', '-', mb_strtoupper(trim($value)));
    }

    /** Partes de la ruta sin el prefijo BASE_PATH: [REFERENCIA] o [REFERENCIA, COLOR]. */
    private function partes_ruta(array $info): array
    {
        $relative = trim((string) ($info['relative'] ?? ''), '/');
        $base = trim(self::BASE_PATH, '/');

        if ($base !== '' && str_starts_with($relative, $base . '/')) {
            $relative = substr($relative, strlen($base) + 1);
        }

        return array_values(array_filter(explode('/', $relative), fn ($part) => $part !== ''));
    }

    /**
     * Recorre los productos de Siigo de una referencia y, a los que NO tienen foto, les sube:
     * 1) la primera foto de la carpeta de su color, o
     * 2) si el color no tiene fotos, la primera foto de la carpeta raíz de la referencia.
     * Nunca borra ni reemplaza fotos existentes.
     */
    private function sincronizar_referencia_siigo(string $token, string $referencia): array
    {
        $products = $this->siigo_buscar_productos($token, $referencia);

        $jpgPorColor = [];
        $jpgRaiz = false; // false = aún no consultada; null = no hay foto en la raíz
        $results = [];

        foreach ($products as $product) {
            $productId = (int) ($product['productID'] ?? 0);
            $description = (string) ($product['description'] ?? $product['name'] ?? '');
            $code = (string) ($product['code'] ?? '');
            $parsed = $this->parsear_descripcion_producto($description);

            if ($productId <= 0 || !$parsed || $parsed['reference'] !== $referencia) {
                continue; // la búsqueda de Siigo es amplia: ignorar lo que no es de esta referencia
            }

            $color = $parsed['color'];
            $base = ['product_id' => $productId, 'code' => $code, 'color' => $color];

            if (!array_key_exists($color, $jpgPorColor)) {
                $jpgPorColor[$color] = $this->obtener_foto_color_jpg($referencia, $color);
            }

            $jpg = $jpgPorColor[$color];
            $origen = 'color';

            if ($jpg === null) {
                if ($jpgRaiz === false) {
                    $jpgRaiz = $this->obtener_primera_foto_jpg(self::BASE_PATH . "/{$referencia}");
                }

                $jpg = $jpgRaiz;
                $origen = 'raiz';
            }

            if ($jpg === null) {
                $results[] = $base + [
                    'status' => 'sin_foto_local',
                    'message' => "No hay fotos en products/{$referencia}/{$color} ni en products/{$referencia}.",
                ];
                continue;
            }

            $results[] = $base + ['origen' => $origen] + $this->procesar_producto_siigo($token, $productId, $jpg, false);
        }

        return [
            'referencia' => $referencia,
            'summary' => array_count_values(array_column($results, 'status')),
            'results' => $results,
        ];
    }

    /** Igual que el sync, pero sin lanzar excepciones: un fallo de Siigo no afecta la carga local. */
    private function completar_fotos_siigo_referencia(string $token, string $referencia): array
    {
        try {
            $data = $this->sincronizar_referencia_siigo($token, $referencia);

            return ['success' => !isset($data['summary']['error'])] + $data;
        } catch (Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'error' => 'No se pudo enviar la foto a Siigo: ' . Str::limit($exception->getMessage(), 200),
            ];
        }
    }

    /** Primera foto (JPG) de products/REFERENCIA/COLOR. Null si no existe la carpeta o no hay fotos. */
    private function obtener_foto_color_jpg(string $referencia, string $color): ?string
    {
        return $this->obtener_primera_foto_jpg(self::BASE_PATH . "/{$referencia}/{$color}");
    }

    /**
     * Toma la primera foto (orden alfabético) que esté directamente en la carpeta, sin
     * entrar a subcarpetas, y la devuelve como JPG. Null si no existe o no hay fotos.
     */
    private function obtener_primera_foto_jpg(string $path): ?string
    {
        if (!Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $files = collect(Storage::disk(self::DISK)->files($path))
            ->filter(fn ($file) => $this->photos->esImagen(basename($file)))
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        $contents = Storage::disk(self::DISK)->get($files->first());

        if ($contents === null || $contents === '') {
            return null;
        }

        return $this->convertir_jpg($contents);
    }

    private function convertir_jpg(string $contents): string
    {
        $manager = new ImageManager(new Driver());

        return $manager->decodeBinary($contents)
            ->encodeUsingFormat(Format::JPEG, quality: 90)
            ->toString();
    }
}

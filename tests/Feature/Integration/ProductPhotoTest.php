<?php

namespace Tests\Feature\Integration;

use App\Http\Requests\Api\Integration\ProductPhoto\BulkUploadRequest;
use App\Support\Integration\LegacyCacheKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;
use ZipArchive;

/**
 * Fotos de producto: las 8 acciones de la API solo piden permiso (sin el usuario 597); el token de Siigo solo se pide
 * al subir desde el explorador. Todo corre sobre un disco falso: nunca toca storage/app/public/products.
 */
class ProductPhotoTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const BASE = '/api/integration/product_photos';

    /** [método, ruta, permiso] de cada acción. */
    private const ROUTES = [
        ['GET', '/search', 'integration.product_photos.search'],
        ['POST', '/upload', 'integration.product_photos.upload'],
        ['DELETE', '/delete', 'integration.product_photos.delete'],
        ['POST', '/bulk_upload', 'integration.product_photos.bulk_upload'],
        ['GET', '/explorer', 'integration.product_photos.explorer'],
        ['POST', '/explorer/folder', 'integration.product_photos.explorer_folder'],
        ['POST', '/explorer/upload', 'integration.product_photos.explorer_upload'],
        ['DELETE', '/explorer/delete', 'integration.product_photos.explorer_delete'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function png(int $width = 40, int $height = 30): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 30, 30));
        ob_start();
        imagepng($image);

        return ob_get_clean();
    }

    private function photo(string $name = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->png());
    }

    /** ZIP de prueba con [nombre dentro del ZIP => contenido]; un nombre que termina en "/" es una carpeta. */
    private function zip(array $entries): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'g4zip') . '.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $name => $contents) {
            str_ends_with($name, '/') ? $zip->addEmptyDir(rtrim($name, '/')) : $zip->addFromString($name, $contents);
        }

        $zip->close();

        return new UploadedFile($path, 'fotos.zip', 'application/zip', null, true);
    }

    private function all(string $reference): array
    {
        return Storage::disk('public')->files("products/{$reference}");
    }

    // ------------------------------------------------------------------ seguridad

    public function test_sin_sesion_responde_401(): void
    {
        foreach (self::ROUTES as [$method, $uri]) {
            $this->json($method, self::BASE . $uri)->assertStatus(401);
        }
    }

    public function test_cada_accion_exige_su_propio_permiso(): void
    {
        foreach (self::ROUTES as [$method, $uri, $permission]) {
            $others = array_values(array_diff(array_column(self::ROUTES, 2), [$permission]));
            $this->actingWith($others);

            $this->json($method, self::BASE . $uri)->assertStatus(403);
        }
    }

    public function test_no_exige_el_usuario_597_de_siigo(): void
    {
        $this->actingWith(['integration.product_photos.upload']);

        $this->post(self::BASE . '/upload', ['referencia' => 'PRUEBA', 'photos' => [$this->photo()]], ['Accept' => 'application/json'])
            ->assertStatus(201);
    }

    // ------------------------------------------------------------------ buscar, subir y borrar

    public function test_busca_las_fotos_de_una_referencia_sin_importar_las_mayusculas(): void
    {
        Storage::disk('public')->put('products/PRUEBA-1/a.webp', 'x');
        $this->actingWith(['integration.product_photos.search']);

        $this->getJson(self::BASE . '/search?referencia=prueba 1')
            ->assertOk()
            ->assertJsonPath('data.referencia', 'PRUEBA-1')
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.photos.0.name', 'a.webp');

        $this->getJson(self::BASE . '/search?referencia=NO-EXISTE')
            ->assertOk()
            ->assertJsonPath('data.exists', false)
            ->assertJsonPath('data.photos', []);
    }

    public function test_sube_fotos_las_convierte_a_webp_y_limpia_el_cache_de_imagenes(): void
    {
        Cache::put(LegacyCacheKeys::inventoryImages('PRUEBA-1'), ['viejo']);
        $this->actingWith(['integration.product_photos.upload']);

        $response = $this->post(self::BASE . '/upload', [
            'referencia' => 'prueba 1',
            'photos' => [$this->photo('uno.png'), $this->photo('dos.png')],
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $response->assertJsonPath('data.upload.referencia', 'PRUEBA-1')->assertJsonCount(2, 'data.upload.uploaded');
        $files = $this->all('PRUEBA-1');
        $this->assertCount(2, $files);
        $this->assertStringEndsWith('.webp', $files[0]);
        $this->assertSame('image/webp', getimagesizefromstring(Storage::disk('public')->get($files[0]))['mime']);
        $this->assertFalse(Cache::has(LegacyCacheKeys::inventoryImages('PRUEBA-1')));
    }

    public function test_la_subida_valida_fotos_referencia_y_tamano(): void
    {
        $this->actingWith(['integration.product_photos.upload']);
        $headers = ['Accept' => 'application/json'];

        $this->post(self::BASE . '/upload', ['referencia' => 'X'], $headers)
            ->assertStatus(422)->assertJsonValidationErrors(['photos']);

        $this->post(self::BASE . '/upload', ['referencia' => 'X', 'photos' => [UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')]], $headers)
            ->assertStatus(422)->assertJsonValidationErrors(['photos.0']);

        $this->post(self::BASE . '/upload', ['referencia' => 'X', 'photos' => [UploadedFile::fake()->image('grande.jpg')->size(10241)]], $headers)
            ->assertStatus(422)->assertJsonValidationErrors(['photos.0']);

        $this->post(self::BASE . '/upload', ['referencia' => '***', 'photos' => [$this->photo()]], $headers)
            ->assertStatus(422)->assertJsonValidationErrors(['referencia']);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_elimina_una_foto_y_rechaza_nombres_con_rutas(): void
    {
        Storage::disk('public')->put('products/PRUEBA-1/a.webp', 'x');
        Cache::put(LegacyCacheKeys::inventoryImages('PRUEBA-1'), ['viejo']);
        $this->actingWith(['integration.product_photos.delete']);

        $this->deleteJson(self::BASE . '/delete', ['referencia' => 'PRUEBA-1', 'filename' => '../../.env'])
            ->assertStatus(422)->assertJsonValidationErrors(['filename']);

        $this->deleteJson(self::BASE . '/delete', ['referencia' => 'PRUEBA-1', 'filename' => 'b.webp'])
            ->assertStatus(422)->assertJsonPath('error.message', 'La foto no existe.');

        $this->deleteJson(self::BASE . '/delete', ['referencia' => 'prueba-1', 'filename' => 'a.webp'])->assertOk();
        Storage::disk('public')->assertMissing('products/PRUEBA-1/a.webp');
        $this->assertFalse(Cache::has(LegacyCacheKeys::inventoryImages('PRUEBA-1')));
    }

    // ------------------------------------------------------------------ carga masiva (ZIP)

    public function test_carga_masiva_por_carpetas(): void
    {
        $this->actingWith(['integration.product_photos.bulk_upload']);

        $zip = $this->zip([
            'AGUILA/uno.png' => $this->png(),
            'AGUILA/dos.png' => $this->png(),
            'VAMPELT/uno.png' => $this->png(),
            'VACIA/' => '',
        ]);

        $this->post(self::BASE . '/bulk_upload', ['zip' => $zip, 'zip_mode' => 'folders'], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonPath('data.bulk_upload.references_processed', 2)
            ->assertJsonPath('data.bulk_upload.images_uploaded', 3)
            ->assertJsonPath('data.bulk_upload.empty_folders', 1);

        $this->assertCount(2, $this->all('AGUILA'));
        $this->assertCount(1, $this->all('VAMPELT'));
    }

    public function test_carga_masiva_por_nombre_de_archivo(): void
    {
        $this->actingWith(['integration.product_photos.bulk_upload']);

        $zip = $this->zip(['AGUILA.png' => $this->png(), 'VAMPELT.png' => $this->png()]);

        $this->post(self::BASE . '/bulk_upload', ['zip' => $zip, 'zip_mode' => 'filenames'], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonPath('data.bulk_upload.images_uploaded', 2);

        $this->assertCount(1, $this->all('AGUILA'));
    }

    public function test_un_zip_con_problemas_no_guarda_ninguna_imagen(): void
    {
        $this->actingWith(['integration.product_photos.bulk_upload']);
        $headers = ['Accept' => 'application/json'];

        $malo = $this->zip([
            'AGUILA/uno.png' => $this->png(),
            'raiz.png' => $this->png(),          // archivo suelto en la raíz
            'minuscula/uno.png' => $this->png(), // nombre de carpeta inválido
            'AGUILA/sub/uno.png' => $this->png(), // subcarpeta
            'AGUILA/nota.txt' => 'hola',          // extensión no permitida
        ]);

        $response = $this->post(self::BASE . '/bulk_upload', ['zip' => $malo, 'zip_mode' => 'folders'], $headers)
            ->assertStatus(422)->assertJsonValidationErrors(['zip']);

        $this->assertGreaterThanOrEqual(4, count($response->json('errors.zip')));
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->post(self::BASE . '/bulk_upload', ['zip' => $this->zip(['AGUILA/uno.png' => $this->png()]), 'zip_mode' => 'otro'], $headers)
            ->assertStatus(422)->assertJsonValidationErrors(['zip_mode']);

        $this->post(self::BASE . '/bulk_upload', ['zip' => UploadedFile::fake()->create('fotos.pdf', 10, 'application/pdf'), 'zip_mode' => 'folders'], $headers)
            ->assertStatus(422)->assertJsonValidationErrors(['zip']);
    }

    public function test_si_una_imagen_no_es_real_se_deshace_toda_la_importacion(): void
    {
        $this->actingWith(['integration.product_photos.bulk_upload']);

        $zip = $this->zip(['AGUILA/uno.png' => $this->png(), 'AGUILA/falsa.png' => 'esto no es una imagen']);

        $this->post(self::BASE . '/bulk_upload', ['zip' => $zip, 'zip_mode' => 'folders'], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_el_limite_del_zip_es_el_de_integration(): void
    {
        $this->assertSame(\App\Http\Requests\Integration\PhotoProduct\BulkUploadRequest::MAX_ZIP_SIZE_KB, BulkUploadRequest::MAX_ZIP_SIZE_KB);
    }

    // ------------------------------------------------------------------ explorador

    public function test_el_explorador_lista_y_rechaza_rutas_que_se_salen_de_products(): void
    {
        Storage::disk('public')->put('products/PRUEBA/NEGRO/a.webp', 'x');
        Storage::disk('public')->put('products/PRUEBA/b.webp', 'x');
        $this->actingWith(['integration.product_photos.explorer']);

        $this->getJson(self::BASE . '/explorer')
            ->assertOk()
            ->assertJsonPath('data.explorer.level', 0)
            ->assertJsonPath('data.explorer.folders.0.name', 'PRUEBA');

        $this->getJson(self::BASE . '/explorer?path=PRUEBA')
            ->assertOk()
            ->assertJsonPath('data.explorer.level', 1)
            ->assertJsonPath('data.explorer.folders.0.name', 'NEGRO')
            ->assertJsonPath('data.explorer.files.0.name', 'b.webp')
            ->assertJsonStructure(['data' => ['explorer' => ['breadcrumb', 'available_colors']]]);

        foreach (['../secreto', 'PRUEBA/../..', 'PRUEBA/NO-ES-COLOR', 'A/NEGRO/EXTRA'] as $path) {
            $this->getJson(self::BASE . '/explorer?path=' . urlencode($path))
                ->assertStatus(422)->assertJsonValidationErrors(['path']);
        }

        $this->getJson(self::BASE . '/explorer?path=NO-EXISTE')->assertStatus(422)->assertJsonPath('error.message', 'La carpeta no existe.');
    }

    public function test_crea_productos_y_colores_con_validaciones(): void
    {
        $this->actingWith(['integration.product_photos.explorer_folder']);

        $this->postJson(self::BASE . '/explorer/folder', ['path' => '', 'name' => 'prueba 2'])
            ->assertStatus(201)->assertJsonPath('data.folder.path', 'PRUEBA-2');
        Storage::disk('public')->assertExists('products/PRUEBA-2');

        $this->postJson(self::BASE . '/explorer/folder', ['path' => '', 'name' => 'PRUEBA-2'])
            ->assertStatus(422)->assertJsonPath('error.message', 'Esa carpeta ya existe.');

        $this->postJson(self::BASE . '/explorer/folder', ['path' => '', 'name' => 'mal!nombre'])
            ->assertStatus(422)->assertJsonValidationErrors(['name']);

        $this->postJson(self::BASE . '/explorer/folder', ['path' => 'PRUEBA-2', 'name' => 'negro'])
            ->assertStatus(201)->assertJsonPath('data.folder.path', 'PRUEBA-2/NEGRO');

        $this->postJson(self::BASE . '/explorer/folder', ['path' => 'PRUEBA-2', 'name' => 'VERDE LIMON'])
            ->assertStatus(422)->assertJsonValidationErrors(['name']);

        $this->postJson(self::BASE . '/explorer/folder', ['path' => 'PRUEBA-2/NEGRO', 'name' => 'ROJO'])
            ->assertStatus(422)->assertJsonValidationErrors(['path']);
    }

    public function test_el_token_de_siigo_invalido_es_422_nunca_401(): void
    {
        Storage::disk('public')->makeDirectory('products/PRUEBA/NEGRO');
        $this->fakeSiigoUser(false);
        $this->actingWith(['integration.product_photos.explorer_upload']);

        $this->post(self::BASE . '/explorer/upload', ['token' => $this->jwt(), 'path' => 'PRUEBA/NEGRO', 'photos' => [$this->photo()]], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['token']);

        $this->post(self::BASE . '/explorer/upload', ['path' => 'PRUEBA/NEGRO', 'photos' => [$this->photo()]], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['token']);

        $this->assertSame([], Storage::disk('public')->files('products/PRUEBA/NEGRO'));
    }

    public function test_sube_a_una_carpeta_de_color_y_reemplaza_la_foto_en_siigo(): void
    {
        Storage::disk('public')->makeDirectory('products/PRUEBA/NEGRO');
        Cache::put(LegacyCacheKeys::inventoryImages('PRUEBA'), ['viejo']);
        $this->fakeSiigoUser(true, 4321); // cualquier usuario de Siigo: ya no se exige el 597
        $this->actingWith(['integration.product_photos.explorer_upload']);

        Http::fake(function ($request) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/FileStorage/File/list') => Http::response([], 200),
                str_contains($url, '/catalog/api/product') => Http::response(['results' => [
                    ['productID' => 501, 'code' => 'P-1', 'description' => 'PRUEBA-NEGRO-BA-35'],
                    ['productID' => 502, 'code' => 'P-2', 'description' => 'PRUEBA-ROJO-BA-35'],
                ]], 200),
                str_contains($url, '/FileStorage/File') => Http::response([], 200),
                default => Http::response([], 404),
            };
        });

        $response = $this->post(self::BASE . '/explorer/upload', ['token' => $this->jwt(), 'path' => 'PRUEBA/NEGRO', 'photos' => [$this->photo()]], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonPath('data.upload.path', 'PRUEBA/NEGRO')
            ->assertJsonPath('data.upload.siigo.success', true)
            ->assertJsonPath('data.upload.siigo.products_found', 1)
            ->assertJsonPath('data.upload.siigo.results.0.status', 'subido');

        $this->assertCount(1, Storage::disk('public')->files('products/PRUEBA/NEGRO'));
        $this->assertFalse(Cache::has(LegacyCacheKeys::inventoryImages('PRUEBA')));
        $this->assertNotEmpty($response->json('data.upload.uploaded'));

        // El envío a Siigo usa el token que mandó la persona
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/FileStorage/File')
            && $request->hasHeader('Authorization', $this->jwt()));
    }

    public function test_un_fallo_de_siigo_no_deshace_la_carga_local(): void
    {
        Storage::disk('public')->makeDirectory('products/PRUEBA');
        $this->fakeSiigoUser();
        $this->actingWith(['integration.product_photos.explorer_upload']);

        Http::fake(['*' => Http::response([], 500)]);

        $this->post(self::BASE . '/explorer/upload', ['token' => $this->jwt(), 'path' => 'PRUEBA', 'photos' => [$this->photo()]], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonPath('data.upload.siigo.success', false);

        $this->assertCount(1, Storage::disk('public')->files('products/PRUEBA'));
    }

    public function test_el_explorador_no_sube_en_la_raiz_ni_en_carpetas_inexistentes(): void
    {
        $this->fakeSiigoUser();
        $this->actingWith(['integration.product_photos.explorer_upload']);
        $headers = ['Accept' => 'application/json'];

        $this->post(self::BASE . '/explorer/upload', ['token' => $this->jwt(), 'path' => '', 'photos' => [$this->photo()]], $headers)
            ->assertStatus(422);

        $this->post(self::BASE . '/explorer/upload', ['token' => $this->jwt(), 'path' => '../fuera', 'photos' => [$this->photo()]], $headers)
            ->assertStatus(422)->assertJsonValidationErrors(['path']);

        $this->post(self::BASE . '/explorer/upload', ['token' => $this->jwt(), 'path' => 'NO-EXISTE', 'photos' => [$this->photo()]], $headers)
            ->assertStatus(422)->assertJsonPath('error.message', 'La carpeta no existe.');
    }

    public function test_elimina_fotos_del_explorador(): void
    {
        Storage::disk('public')->put('products/PRUEBA/NEGRO/a.webp', 'x');
        $this->actingWith(['integration.product_photos.explorer_delete']);

        $this->deleteJson(self::BASE . '/explorer/delete', ['path' => 'PRUEBA/NEGRO', 'filename' => '../a.webp'])
            ->assertStatus(422)->assertJsonValidationErrors(['filename']);

        $this->deleteJson(self::BASE . '/explorer/delete', ['path' => '', 'filename' => 'a.webp'])
            ->assertStatus(422)->assertJsonValidationErrors(['path']);

        $this->deleteJson(self::BASE . '/explorer/delete', ['path' => 'PRUEBA/NEGRO', 'filename' => 'a.webp'])->assertOk();
        Storage::disk('public')->assertMissing('products/PRUEBA/NEGRO/a.webp');
    }

    // ------------------------------------------------------------------ límite de peticiones

    /** El contador `integration-write` es propio: no se suma al `throttle:30,1` del grupo (si se sumara, el límite real sería 5). */
    public function test_el_limite_de_escritura_es_de_10_por_minuto_sin_contar_doble(): void
    {
        $this->actingWith(['integration.product_photos.bulk_upload']);

        for ($i = 1; $i <= 10; $i++) {
            $this->postJson(self::BASE . '/bulk_upload', [])->assertStatus(422);
        }

        $this->postJson(self::BASE . '/bulk_upload', [])->assertStatus(429);
    }
}

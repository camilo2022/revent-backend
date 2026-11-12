<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoUserService;
use App\Services\SiigoPurchaseOCPdfService;
use App\Services\SiigoTransferNTPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

/** Buscar documento en la API: una ruta por tipo, token JWT para la NT y cookie del monolito para la OC. */
class SearchDocumentTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE = '/api/integration/search_document';

    private const JWT = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.firma-de-prueba';

    private const COOKIE = 'TKNSGRDDREVENTCALZADOSAS=' . self::JWT . '; .ASPXAUTH=abc123; ASP.NET_SessionId=sess456';

    private function full(): User
    {
        return User::where('username', 'operaciones')->firstOrFail();
    }

    private function siigoUser(bool $valid = true): void
    {
        $this->mock(SiigoUserService::class, function (MockInterface $mock) use ($valid) {
            $mock->shouldReceive('fromToken')->with(self::JWT)->andReturn($valid
                ? ['success' => true, 'data' => ['id' => 597, 'user' => 'operaciones@revent.com.co', 'name' => 'Operaciones'], 'message' => 'ok']
                : ['success' => false, 'data' => [], 'message' => 'Error de autenticación']);
        });
    }

    private function client(): void
    {
        $this->mock(SiigoClient::class, fn (MockInterface $mock) => $mock->shouldReceive('token')->andReturn('token-de-la-aplicacion'));
    }

    private function document(): array
    {
        return ['ACEntryID' => 555, 'Code' => 'NT-1-555', 'DocName' => 'NT-1-555'];
    }

    // ------------------------------------------------------------------ Sesión, permisos y tipos

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson(self::BASE . '/types')->assertStatus(401);
        $this->postJson(self::BASE . '/transfer_note', ['query' => 'NT-1', 'token' => self::JWT])->assertStatus(401);
    }

    public function test_sin_permiso_responde_403(): void
    {
        Sanctum::actingAs(User::where('username', 'alegra')->firstOrFail());

        $this->getJson(self::BASE . '/types')->assertStatus(403);
        $this->postJson(self::BASE . '/transfer_note', ['query' => 'NT-1', 'token' => self::JWT])->assertStatus(403);
        $this->postJson(self::BASE . '/purchase_order', ['query' => 'OC-1', 'cookie' => self::COOKIE])->assertStatus(403);
        $this->postJson(self::BASE . '/transfer_note_pdf', ['query' => 'NT-1', 'token' => self::JWT])->assertStatus(403);
        $this->postJson(self::BASE . '/purchase_order_pdf', ['query' => 'OC-1', 'cookie' => self::COOKIE])->assertStatus(403);
    }

    public function test_lista_los_tipos_con_la_credencial_de_cada_uno(): void
    {
        Sanctum::actingAs($this->full());

        $response = $this->getJson(self::BASE . '/types')->assertOk();

        $types = collect($response->json('data.types'))->keyBy('id');
        $this->assertSame('token', $types[23]['credential']);
        $this->assertSame('cookie', $types[19]['credential']);
        $this->assertSame('NOTA DE TRASLADO', $types[23]['name']);
    }

    // ------------------------------------------------------------------ Nota de traslado (token)

    public function test_busca_una_nota_de_traslado_con_sus_productos(): void
    {
        $this->siigoUser();
        $this->client();
        $this->mock(SiigoTransferNTPdfService::class, function (MockInterface $mock) {
            $mock->shouldReceive('find_document')->once()->with('token-de-la-aplicacion', 'NT-1-555', 23)->andReturn($this->document());
            $mock->shouldReceive('transfer_note_html')->once()->with('token-de-la-aplicacion', 555)->andReturn('<html></html>');
            $mock->shouldReceive('parse_transfer_items')->once()->andReturn([['Code' => 'A1', 'Quantity' => 2]]);
        });
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/transfer_note', ['query' => ' NT-1-555 ', 'token' => self::JWT])
            ->assertOk()
            ->assertJsonPath('data.document.ACEntryID', 555)
            ->assertJsonPath('data.document.Items.0.Code', 'A1');
    }

    public function test_un_documento_inexistente_responde_200_con_document_null(): void
    {
        $this->siigoUser();
        $this->client();
        $this->mock(SiigoTransferNTPdfService::class, fn (MockInterface $mock) => $mock->shouldReceive('find_document')->andReturn(null));
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/transfer_note', ['query' => 'NT-X', 'token' => self::JWT])
            ->assertOk()
            ->assertJsonPath('data.document', null);
    }

    public function test_el_token_invalido_es_422_y_nunca_401(): void
    {
        $this->client();
        Sanctum::actingAs($this->full());

        // Formato que no es un JWT: ni siquiera se consulta a Siigo
        $this->postJson(self::BASE . '/transfer_note', ['query' => 'NT-1', 'token' => 'no-es-un-jwt'])
            ->assertStatus(422)->assertJsonValidationErrors('token');

        // JWT que Siigo rechaza
        $this->siigoUser(false);
        $this->postJson(self::BASE . '/transfer_note', ['query' => 'NT-1', 'token' => self::JWT])
            ->assertStatus(422)->assertJsonValidationErrors('token');
    }

    public function test_el_pdf_de_la_nota_se_entrega_como_archivo(): void
    {
        $this->siigoUser();
        $this->client();
        $this->mock(SiigoTransferNTPdfService::class, function (MockInterface $mock) {
            $mock->shouldReceive('find_document')->once()->andReturn($this->document());
            $mock->shouldReceive('transfer_note_pdf')->once()->andReturn(Pdf::loadHTML('<p>Nota de traslado</p>'));
            $mock->shouldReceive('pdf_filename')->once()->andReturn('NT-1-555.pdf');
        });
        Sanctum::actingAs($this->full());

        $response = $this->postJson(self::BASE . '/transfer_note_pdf', ['query' => 'NT-1-555', 'token' => self::JWT])->assertOk();

        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('NT-1-555.pdf', $response->headers->get('content-disposition'));
    }

    public function test_el_pdf_de_una_nota_inexistente_es_422(): void
    {
        $this->siigoUser();
        $this->client();
        Sanctum::actingAs($this->full());

        $this->mock(SiigoTransferNTPdfService::class, fn (MockInterface $mock) => $mock->shouldReceive('find_document')->andReturn(null));
        $this->postJson(self::BASE . '/transfer_note_pdf', ['query' => 'NT-X', 'token' => self::JWT])
            ->assertStatus(422)->assertJsonPath('error.message', 'No se encontró la nota de traslado en Siigo.');
    }

    public function test_el_pdf_de_una_nota_sin_vista_en_siigo_es_422(): void
    {
        $this->siigoUser();
        $this->client();
        Sanctum::actingAs($this->full());

        $this->mock(SiigoTransferNTPdfService::class, function (MockInterface $mock) {
            $mock->shouldReceive('find_document')->andReturn($this->document());
            $mock->shouldReceive('transfer_note_pdf')->andReturn(null);
        });
        $this->postJson(self::BASE . '/transfer_note_pdf', ['query' => 'NT-1-555', 'token' => self::JWT])
            ->assertStatus(422)->assertJsonPath('error.message', 'No se pudo obtener la vista del documento en Siigo.');
    }

    // ------------------------------------------------------------------ Orden de compra (cookie)

    public function test_busca_una_orden_de_compra_con_la_cookie_completa(): void
    {
        $this->siigoUser();
        $this->client();
        $this->mock(SiigoTransferNTPdfService::class, function (MockInterface $mock) {
            $mock->shouldReceive('find_document')->once()->with('token-de-la-aplicacion', 'OC-1-77', 19)->andReturn(['ACEntryID' => 77, 'Code' => 'OC-1-77']);
        });
        $this->mock(SiigoPurchaseOCPdfService::class, function (MockInterface $mock) {
            // La cookie llega completa al servicio, igual que en la pantalla web
            $mock->shouldReceive('purchase_order_html')->once()->with(self::COOKIE, ['ACEntryID' => 77, 'Code' => 'OC-1-77'])->andReturn('<html></html>');
            $mock->shouldReceive('parse_purchase_items')->once()->andReturn([['Code' => 'P1']]);
        });
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/purchase_order', ['query' => 'OC-1-77', 'cookie' => self::COOKIE])
            ->assertOk()
            ->assertJsonPath('data.document.Items.0.Code', 'P1');
    }

    public function test_la_cookie_incompleta_o_vencida_es_422_y_nunca_401(): void
    {
        $this->client();
        Sanctum::actingAs($this->full());

        // Falta .ASPXAUTH y ASP.NET_SessionId
        $this->postJson(self::BASE . '/purchase_order', ['query' => 'OC-1', 'cookie' => 'TKNSGRDDREVENTCALZADOSAS=' . self::JWT])
            ->assertStatus(422)->assertJsonValidationErrors('cookie');

        // Una cookie no sirve en lugar del token y el token no sirve en lugar de la cookie
        $this->postJson(self::BASE . '/purchase_order', ['query' => 'OC-1', 'cookie' => self::JWT])
            ->assertStatus(422)->assertJsonValidationErrors('cookie');

        $this->siigoUser(false);
        $this->postJson(self::BASE . '/purchase_order', ['query' => 'OC-1', 'cookie' => self::COOKIE])
            ->assertStatus(422)->assertJsonValidationErrors('cookie');
    }

    public function test_el_pdf_de_la_orden_se_entrega_como_archivo(): void
    {
        $this->siigoUser();
        $this->client();
        $this->mock(SiigoTransferNTPdfService::class, fn (MockInterface $mock) => $mock->shouldReceive('find_document')->andReturn(['ACEntryID' => 77]));
        $this->mock(SiigoPurchaseOCPdfService::class, function (MockInterface $mock) {
            $mock->shouldReceive('purchase_order_pdf')->once()->with(self::COOKIE, ['ACEntryID' => 77])->andReturn(Pdf::loadHTML('<p>Orden</p>'));
            $mock->shouldReceive('pdf_filename')->once()->andReturn('OC-1-77.pdf');
        });
        Sanctum::actingAs($this->full());

        $response = $this->postJson(self::BASE . '/purchase_order_pdf', ['query' => 'OC-1-77', 'cookie' => self::COOKIE])->assertOk();

        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('OC-1-77.pdf', $response->headers->get('content-disposition'));
    }

    public function test_el_pdf_de_la_orden_sin_vista_pide_revisar_la_cookie(): void
    {
        $this->siigoUser();
        $this->client();
        $this->mock(SiigoTransferNTPdfService::class, fn (MockInterface $mock) => $mock->shouldReceive('find_document')->andReturn(['ACEntryID' => 77]));
        $this->mock(SiigoPurchaseOCPdfService::class, fn (MockInterface $mock) => $mock->shouldReceive('purchase_order_pdf')->andReturn(null));
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/purchase_order_pdf', ['query' => 'OC-1-77', 'cookie' => self::COOKIE])
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'No se pudo obtener la vista del documento en Siigo. Revisa que la cookie no haya expirado.');
    }
}

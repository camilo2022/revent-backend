<?php

namespace Tests\Feature\Integration;

use App\Http\Controllers\Integration\TransferApprovalSiigoController;
use App\Jobs\Integration\ImportMasiveTransferSiigoJob;
use App\Models\User;
use App\Services\Siigo\SiigoTransferApprovalService;
use App\Services\Siigo\SiigoUserService;
use App\Support\Integration\LegacyCacheKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

/** Aprobación de traslados en la API: acceso por tienda asignada, token de Siigo solo para la acción y el mismo job. */
class TransferApprovalTest extends TestCase
{
    use DatabaseTransactions;

    private const SEARCH = '/api/integration/transfer_approval/search';

    private const CONFIRM = '/api/integration/transfer_approval/confirm';

    private const JWT = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.firma-de-prueba';

    private const NOTE_CODE = 'NT-1-99001';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(LegacyCacheKeys::transferApprovalLock(self::NOTE_CODE));
    }

    protected function tearDown(): void
    {
        Cache::forget(LegacyCacheKeys::transferApprovalLock(self::NOTE_CODE));

        parent::tearDown();
    }

    private function alegra(): User
    {
        return User::where('username', 'alegra')->firstOrFail();
    }

    private function siigoUser(bool $valid = true): void
    {
        $this->mock(SiigoUserService::class, function (MockInterface $mock) use ($valid) {
            $mock->shouldReceive('fromToken')->andReturn($valid
                ? ['success' => true, 'data' => ['id' => 1234, 'user' => 'tienda@revent.com.co', 'name' => 'Tienda'], 'message' => 'ok']
                : ['success' => false, 'data' => [], 'message' => 'Error de autenticación']);
        });
    }

    /** Nota de traslado hacia el tránsito indicado (70 = tránsito de Alegra, 71 = tránsito de Mayales). */
    private function note(int $transitId = 70, int $destinationId = 3): array
    {
        return [
            'code' => self::NOTE_CODE,
            'id' => 99001,
            'date' => '2026-11-10',
            'origin' => 'PRINCIPAL',
            'transit' => ['id' => $transitId, 'name' => "TRANSITO {$transitId}"],
            'destination' => ['id' => $destinationId, 'name' => "BODEGA {$destinationId}"],
            'items' => [
                ['code' => 'A1', 'description' => 'Producto A1', 'requested' => 5],
                ['code' => 'B2', 'description' => 'Producto B2', 'requested' => 2],
            ],
            'images' => [],
        ];
    }

    private function approval(?array $note, array $available = ['A1' => 4, 'B2' => 2]): void
    {
        // partialMock conserva warehouses() real (BODEGAS del job) y simula solo las consultas a Siigo
        $this->partialMock(SiigoTransferApprovalService::class, function (MockInterface $mock) use ($note, $available) {
            $mock->shouldReceive('find')->andReturn($note
                ? ['note' => $note, 'error' => null]
                : ['note' => null, 'error' => 'No se encontró la nota de traslado en Siigo.']);
            $mock->shouldReceive('availability')->andReturn($available);
        });
    }

    private function body(array $override = []): array
    {
        return array_replace([
            'token' => self::JWT,
            'query' => self::NOTE_CODE,
            'items' => [['code' => 'A1', 'quantity' => 3], ['code' => 'B2', 'quantity' => 0]],
        ], $override);
    }

    // ------------------------------------------------------------------ Sesión, permisos y credencial

    public function test_sin_sesion_responde_401(): void
    {
        $this->postJson(self::SEARCH, $this->body())->assertStatus(401);
        $this->postJson(self::CONFIRM, $this->body())->assertStatus(401);
    }

    public function test_sin_permiso_responde_403(): void
    {
        $user = $this->alegra();
        $user->removeRole('integration.transfer_approval');
        $user->revokePermissionTo(['integration.transfer_approval.search', 'integration.transfer_approval.confirm']);
        Sanctum::actingAs($user->fresh());

        $this->postJson(self::SEARCH, $this->body())->assertStatus(403);
        $this->postJson(self::CONFIRM, $this->body())->assertStatus(403);
    }

    public function test_un_token_de_siigo_invalido_es_422_y_nunca_401(): void
    {
        $this->siigoUser(false);
        Sanctum::actingAs($this->alegra());

        $this->postJson(self::SEARCH, $this->body())->assertStatus(422)->assertJsonValidationErrors('token');
    }

    // ------------------------------------------------------------------ Búsqueda

    public function test_busca_la_nota_de_su_tienda_con_lo_maximo_a_aprobar(): void
    {
        $this->siigoUser();
        $this->approval($this->note());
        Sanctum::actingAs($this->alegra());

        $response = $this->postJson(self::SEARCH, $this->body())->assertOk();

        $response->assertJsonPath('data.note.code', self::NOTE_CODE)
            ->assertJsonPath('data.note.items.0.available', 4)
            ->assertJsonPath('data.note.items.0.max', 4)   // menor entre lo pedido (5) y lo disponible (4)
            ->assertJsonPath('data.note.items.1.max', 2);
    }

    public function test_una_nota_de_otra_tienda_responde_422_de_acceso(): void
    {
        $this->siigoUser();
        $this->approval($this->note(71, 9));
        Sanctum::actingAs($this->alegra());

        $this->postJson(self::SEARCH, $this->body())
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'No tienes acceso a la bodega TRANSITO 71. Pide que te asignen la tienda.');
    }

    public function test_el_supervisor_accede_a_las_notas_de_todas_las_bodegas(): void
    {
        $this->siigoUser();
        $this->approval($this->note(71, 9));
        Sanctum::actingAs(User::where('username', 'operaciones')->firstOrFail());

        $this->postJson(self::SEARCH, $this->body())->assertOk()->assertJsonPath('data.note.transit.id', 71);
    }

    public function test_una_nota_inexistente_es_422_con_el_motivo(): void
    {
        $this->siigoUser();
        $this->approval(null);
        Sanctum::actingAs($this->alegra());

        $this->postJson(self::SEARCH, $this->body())
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'No se encontró la nota de traslado en Siigo.');
    }

    // ------------------------------------------------------------------ Confirmación

    public function test_confirma_y_despacha_el_mismo_job_que_el_flujo_web(): void
    {
        Queue::fake();
        $this->siigoUser();
        $this->approval($this->note());
        Sanctum::actingAs($this->alegra());

        $this->postJson(self::CONFIRM, $this->body())
            ->assertStatus(202)
            ->assertJsonPath('data.approval.code', self::NOTE_CODE)
            ->assertJsonPath('data.approval.units', 3);

        // El correo del resultado es el mismo que calcula la pantalla web (se lee su método privado, sin modificarlo)
        $legacy = new \ReflectionMethod(TransferApprovalSiigoController::class, 'warehouseEmail');
        $legacy->setAccessible(true);
        $expectedEmail = $legacy->invoke(app(TransferApprovalSiigoController::class), 70);

        Queue::assertPushed(ImportMasiveTransferSiigoJob::class, function (ImportMasiveTransferSiigoJob $job) use ($expectedEmail) {
            $transfer = $this->property($job, 'transfer');
            $header = $transfer['traslado']->first();

            return $this->property($job, 'email') === $expectedEmail
                && $this->property($job, 'userId') === auth()->id()
                && $job->queue === 'masive-transfers'
                && $header['token'] === self::JWT
                && $header['validar_disponible'] === 'SI'
                && $header['tipo'] === 'DIRECTO'
                && $header['observacion'] === 'APROBACION DE TRASLADO DE TRANSITO: ' . self::NOTE_CODE
                && $transfer['traslado_detalles']->all() === [[
                    'codigo' => 'A1', 'descripcion' => 'Producto A1', 'bodega_salida' => 70, 'bodega_entrada' => 3, 'cantidad' => 3,
                ]];
        });
    }

    public function test_el_mismo_bloqueo_del_legacy_responde_429(): void
    {
        Queue::fake();
        $this->siigoUser();
        $this->approval($this->note());
        Sanctum::actingAs($this->alegra());

        $this->postJson(self::CONFIRM, $this->body())->assertStatus(202);
        $this->postJson(self::CONFIRM, $this->body())->assertStatus(429);

        Queue::assertPushed(ImportMasiveTransferSiigoJob::class, 1);
        $this->assertFalse(Cache::add(LegacyCacheKeys::transferApprovalLock(self::NOTE_CODE), true, 5));
    }

    public function test_no_aprueba_mas_que_el_maximo_ni_productos_ajenos(): void
    {
        Queue::fake();
        $this->siigoUser();
        $this->approval($this->note());
        Sanctum::actingAs($this->alegra());

        $this->postJson(self::CONFIRM, $this->body(['items' => [['code' => 'A1', 'quantity' => 5]]]))
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'A1: la cantidad (5) supera el máximo permitido (4).');

        $this->postJson(self::CONFIRM, $this->body(['items' => [['code' => 'ZZ', 'quantity' => 1]]]))
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'El producto ZZ no pertenece a la nota de traslado.');

        Queue::assertNothingPushed();
    }

    public function test_la_confirmacion_valida_las_cantidades(): void
    {
        Queue::fake();
        $this->siigoUser();
        Sanctum::actingAs($this->alegra());

        $this->postJson(self::CONFIRM, $this->body(['items' => [['code' => 'A1', 'quantity' => 0]]]))
            ->assertStatus(422)->assertJsonValidationErrors('items');

        $this->postJson(self::CONFIRM, $this->body(['items' => [['code' => 'A1', 'quantity' => -1]]]))
            ->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');

        $this->postJson(self::CONFIRM, $this->body(['items' => []]))
            ->assertStatus(422)->assertJsonValidationErrors('items');

        $this->postJson(self::CONFIRM, $this->body(['items' => [['code' => 'A1', 'quantity' => 1], ['code' => 'A1', 'quantity' => 2]]]))
            ->assertStatus(422)->assertJsonValidationErrors('items.0.code');

        Queue::assertNothingPushed();
    }

    public function test_la_confirmacion_de_una_nota_de_otra_tienda_no_despacha_nada(): void
    {
        Queue::fake();
        $this->siigoUser();
        $this->approval($this->note(71, 9));
        Sanctum::actingAs($this->alegra());

        $this->postJson(self::CONFIRM, $this->body())->assertStatus(422);

        Queue::assertNothingPushed();
        $this->assertTrue(Cache::add(LegacyCacheKeys::transferApprovalLock(self::NOTE_CODE), true, 5));
    }

    private function property(object $job, string $name): mixed
    {
        $property = new \ReflectionProperty($job, $name);
        $property->setAccessible(true);

        return $property->getValue($job);
    }
}

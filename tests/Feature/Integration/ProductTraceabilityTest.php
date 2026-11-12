<?php

namespace Tests\Feature\Integration;

use App\Services\Siigo\SiigoTransitAuditService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Trazabilidad de productos: bodegas por acceso, plantilla, consulta y descarga con la misma auditoría. */
class ProductTraceabilityTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const BASE = '/api/integration/product_traceability';

    private const WAREHOUSES = [
        ['id' => -1, 'name' => 'SIN ASIGNAR', 'transito' => false],
        ['id' => 3, 'name' => 'ALEGRA', 'transito' => true],
        ['id' => 9, 'name' => 'MAYALES', 'transito' => true],
    ];

    private function audit(): array
    {
        return [
            'warehouse' => 'ALEGRA',
            'transit' => 'TRANSITO ALEGRA',
            'start_date' => '2026-11-01',
            'products' => [['code' => 'PROD1', 'name' => 'Producto', 'factory_reference' => 'REF', 'rows' => []]],
            'summary' => ['transito' => 0, 'sin_movimientos' => 1],
        ];
    }

    private function codesFile(array $codes)
    {
        return $this->xlsx(['Hoja1' => [['codigo'], ...array_map(fn ($code) => [$code], $codes)]]);
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson(self::BASE . '/warehouses')->assertStatus(401);
        $this->getJson(self::BASE . '/format')->assertStatus(401);
        $this->postJson(self::BASE . '/search', [])->assertStatus(401);
        $this->postJson(self::BASE . '/download', [])->assertStatus(401);
    }

    public function test_cada_accion_exige_su_permiso(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->actingWith(['integration.product_traceability.warehouses']);

        $this->getJson(self::BASE . '/format')->assertStatus(403);
        $this->postJson(self::BASE . '/search', [])->assertStatus(403);
        $this->postJson(self::BASE . '/download', [])->assertStatus(403);

        $this->actingWith(['integration.product_traceability.search']);
        $this->getJson(self::BASE . '/warehouses')->assertStatus(403);
    }

    public function test_lista_solo_las_bodegas_del_usuario_sin_sin_asignar(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->actingWith(['integration.product_traceability.warehouses']);

        $this->getJson(self::BASE . '/warehouses')
            ->assertOk()
            ->assertExactJson(['data' => ['warehouses' => [['id' => 3, 'name' => 'ALEGRA', 'transito' => true]]], 'message' => 'Operación completada con éxito. ', 'error' => false]);
    }

    public function test_el_supervisor_ve_todas_las_bodegas_reales(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->actingWith(['integration.product_traceability.warehouses', 'stores.access_all'], 'operaciones');

        $this->getJson(self::BASE . '/warehouses')
            ->assertOk()
            ->assertJsonPath('data.warehouses.*.id', [3, 9]);
    }

    public function test_descarga_la_plantilla(): void
    {
        $this->actingWith(['integration.product_traceability.format']);

        $response = $this->get(self::BASE . '/format', ['Accept' => 'application/json']);

        if (!is_file(public_path('storage/formats/formato_trazabilidad_producto.xlsx'))) {
            $response->assertStatus(422);

            return;
        }

        $response->assertOk();
        $this->assertStringContainsString('formato_trazabilidad_producto.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_consulta_la_trazabilidad_con_los_codigos_del_archivo(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->mock(SiigoTransitAuditService::class, function ($mock) {
            $mock->shouldReceive('audit')
                ->once()
                ->withArgs(fn ($token, $warehouseId, $startDate, $codes, $warehouses) => $warehouseId === 3
                    && $startDate === '2026-11-01'
                    && $codes === ['PROD1', 'PROD2']
                    && count($warehouses) === 2)
                ->andReturn($this->audit());
        });
        $this->actingWith(['integration.product_traceability.search']);

        $this->postJson(self::BASE . '/search', [
            'warehouse_id' => '3', 'start_date' => '2026-11-01', 'file' => $this->codesFile(['prod1', ' PROD2 ', 'PROD1']),
        ])
            ->assertOk()
            ->assertJsonPath('data.audit.warehouse', 'ALEGRA')
            ->assertJsonPath('data.audit.transit', 'TRANSITO ALEGRA')
            ->assertJsonPath('data.audit.products.0.code', 'PROD1')
            ->assertJsonPath('data.audit.summary.sin_movimientos', 1);
    }

    public function test_descarga_el_excel_de_la_auditoria(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->mock(SiigoTransitAuditService::class, fn ($mock) => $mock->shouldReceive('audit')->once()->andReturn($this->audit()));
        $this->actingWith(['integration.product_traceability.download']);

        $response = $this->post(self::BASE . '/download', [
            'warehouse_id' => '3', 'start_date' => '2026-11-01', 'file' => $this->codesFile(['PROD1']),
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertStringContainsString('TRAZABILIDAD DE PRODUCTOS.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_un_archivo_sin_codigos_es_un_error_de_validacion(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->mock(SiigoTransitAuditService::class, fn ($mock) => $mock->shouldReceive('audit')->never());
        $this->actingWith(['integration.product_traceability.search']);

        $this->postJson(self::BASE . '/search', [
            'warehouse_id' => '3', 'start_date' => '2026-11-01', 'file' => $this->codesFile([]),
        ])->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_rechaza_una_bodega_sin_acceso_o_inexistente(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->mock(SiigoTransitAuditService::class, fn ($mock) => $mock->shouldReceive('audit')->never());
        $this->actingWith(['integration.product_traceability.search']);
        $file = fn () => $this->codesFile(['PROD1']);

        $response = $this->postJson(self::BASE . '/search', ['warehouse_id' => '9', 'start_date' => '2026-11-01', 'file' => $file()])
            ->assertStatus(422)->assertJsonValidationErrors('warehouse_id');
        $this->assertStringContainsString('No tienes acceso a la bodega', $response->json('errors.warehouse_id.0'));

        $this->postJson(self::BASE . '/search', ['warehouse_id' => '999', 'start_date' => '2026-11-01', 'file' => $file()])
            ->assertStatus(422)->assertJsonValidationErrors('warehouse_id');
    }

    public function test_valida_la_fecha_y_el_archivo(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->actingWith(['integration.product_traceability.search']);

        $this->postJson(self::BASE . '/search', ['warehouse_id' => '3'])
            ->assertStatus(422)->assertJsonValidationErrors(['start_date', 'file']);
    }
}

<?php

namespace Tests\Feature\Integration;

use App\Jobs\ImportMasivePurchaseOrderSiigoJob;
use App\Services\Integration\MasivePurchaseOrderFormatService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Maatwebsite\Excel\Concerns\FromArray;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Órdenes de compra masivas: tipos, formato (cookie de Siigo) y carga validada que despacha el mismo job que Integration. */
class MasivePurchaseOrdersTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const TYPES = '/api/integration/masive_purchase_orders/types';

    private const FORMAT = '/api/integration/masive_purchase_orders/format';

    private const UPLOAD = '/api/integration/masive_purchase_orders/upload';

    /** Cookie con lo mínimo que exige la API: TKNSGRDDREVENTCALZADOSAS (JWT), .ASPXAUTH y ASP.NET_SessionId. */
    private function cookie(): string
    {
        return 'TKNSGRDDREVENTCALZADOSAS=' . $this->jwt() . '; .ASPXAUTH=abc123; ASP.NET_SessionId=def456';
    }

    private function order(array $details, ?array $header = null)
    {
        return $this->xlsx([
            'orden_compra' => $header ?? [
                ['cookie', 'tipo', 'tipo_orden', 'fecha', 'proveedor', 'observaciones'],
                [$this->cookie(), 23200, 'Compra', '2026-11-01', '900123456', 'Prueba'],
            ],
            'orden_compra_detalles' => [['tipo_detalle', 'item', 'bodega', 'cantidad', 'valor_unitario', 'descuento'], ...$details],
        ]);
    }

    private function upload($file, array $extra = [])
    {
        return $this->postJson(self::UPLOAD, ['file' => $file, 'email' => 'cualquiera@gmail.com', 'referencia' => 'REF1', ...$extra]);
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson(self::TYPES)->assertStatus(401);
        $this->postJson(self::FORMAT, [])->assertStatus(401);
        $this->postJson(self::UPLOAD, [])->assertStatus(401);
    }

    public function test_cada_accion_exige_su_permiso(): void
    {
        Queue::fake();

        $this->actingWith(['integration.masive_purchase_orders.types']);
        $this->postJson(self::FORMAT, [])->assertStatus(403);
        $this->postJson(self::UPLOAD, [])->assertStatus(403);

        $this->actingWith(['integration.masive_purchase_orders.upload']);
        $this->getJson(self::TYPES)->assertStatus(403);

        Queue::assertNothingPushed();
    }

    public function test_lista_los_cuatro_tipos_de_orden_de_compra(): void
    {
        $this->actingWith(['integration.masive_purchase_orders.types']);

        $types = $this->getJson(self::TYPES)->assertOk()->json('data.types');

        $this->assertSame([23200, 27279, 28029, 30480], collect($types)->pluck('id')->all());
        $this->assertSame('OC-1-Orden de compra principal', $types[0]['name']);
    }

    public function test_el_formato_exige_la_cookie_y_un_tipo_valido(): void
    {
        $this->fakeSiigoUser();
        $this->actingWith(['integration.masive_purchase_orders.format']);

        $this->postJson(self::FORMAT, ['purchase_order_type_id' => 23200])->assertStatus(422)->assertJsonValidationErrors('cookie');
        $this->postJson(self::FORMAT, ['cookie' => 'solo-una-cookie', 'purchase_order_type_id' => 23200])->assertStatus(422)->assertJsonValidationErrors('cookie');
        $this->postJson(self::FORMAT, ['cookie' => $this->cookie(), 'purchase_order_type_id' => 99999])->assertStatus(422)->assertJsonValidationErrors('purchase_order_type_id');
    }

    public function test_una_cookie_vencida_es_un_422_nunca_un_401(): void
    {
        $this->fakeSiigoUser(false);
        $this->actingWith(['integration.masive_purchase_orders.format']);

        $this->postJson(self::FORMAT, ['cookie' => $this->cookie(), 'purchase_order_type_id' => 23200])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cookie');
    }

    public function test_descarga_el_formato_usando_el_token_de_la_cookie(): void
    {
        $this->fakeSiigoUser();

        $this->mock(MasivePurchaseOrderFormatService::class, function ($mock) {
            $mock->shouldReceive('build')
                ->once()
                ->with($this->jwt(), $this->cookie(), '27279')
                ->andReturn([
                    new class implements FromArray {
                        public function array(): array
                        {
                            return [['cookie', 'tipo']];
                        }
                    },
                    'formato_OC_2',
                ]);
        });

        $this->actingWith(['integration.masive_purchase_orders.format']);

        $response = $this->post(self::FORMAT, ['cookie' => $this->cookie(), 'purchase_order_type_id' => 27279], ['Accept' => 'application/json'])->assertOk();

        $this->assertStringContainsString('formato_OC_2.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_carga_una_orden_valida_y_despacha_el_job_con_las_bodegas_como_id(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_purchase_orders.upload']);

        $file = $this->order([['0', 'REF1-NEGRO-JEAN-30', '3-ALEGRA', 5, 10000, ''], ['0', 'REF1-NEGRO-JEAN-32', 3, 2, 10000, '']]);

        $this->upload($file)
            ->assertStatus(202)
            ->assertJsonPath('data.upload.referencia', 'REF1')
            ->assertJsonPath('data.upload.lines', 2);

        Queue::assertPushedOn('masive-purchase-orders', ImportMasivePurchaseOrderSiigoJob::class);
        Queue::assertPushed(ImportMasivePurchaseOrderSiigoJob::class, 1);
        Queue::assertPushed(ImportMasivePurchaseOrderSiigoJob::class, function ($job) {
            $order = $this->jobValue($job, 'order');

            return $this->jobValue($job, 'email') === 'cualquiera@gmail.com'
                && $this->jobValue($job, 'referencia') === 'REF1'
                && $order['orden_compra'][0]['proveedor'] == '900123456'
                && $order['orden_compra_detalles'][0]['bodega'] === 3
                && $order['orden_compra_detalles'][1]['bodega'] === 3;
        });
    }

    public function test_una_bodega_sin_acceso_responde_422_y_no_despacha_nada(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_purchase_orders.upload']);

        $this->upload($this->order([['0', 'REF1-NEGRO-JEAN-30', '9-MAYALES', 5, 10000, '']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        Queue::assertNothingPushed();
    }

    public function test_quien_tiene_acceso_a_todas_las_bodegas_puede_cargar_en_cualquiera(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_purchase_orders.upload', 'stores.access_all'], 'operaciones');

        $this->upload($this->order([['0', 'REF1-NEGRO-JEAN-30', '9-MAYALES', 5, 10000, '']]))->assertStatus(202);

        Queue::assertPushed(ImportMasivePurchaseOrderSiigoJob::class, 1);
    }

    public function test_valida_el_contenido_del_excel(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_purchase_orders.upload']);

        // Sin las hojas del formato
        $this->upload($this->xlsx(['otra' => [['a'], [1]]]))->assertStatus(422)->assertJsonValidationErrors('file');

        // Sin filas de detalle
        $this->upload($this->order([]))->assertStatus(422)->assertJsonValidationErrors('file');

        // Cantidad cero, tipo de detalle inválido y valor que no es número
        $response = $this->upload($this->order([
            ['0', 'REF1-NEGRO-JEAN-30', 3, '0', 10000, ''],
            ['7', 'REF1-NEGRO-JEAN-30', 3, 1, 10000, ''],
            ['0', 'REF1-NEGRO-JEAN-30', 3, 1, 'mucho', ''],
        ]))->assertStatus(422);

        $messages = implode(' ', $response->json('errors.file'));
        $this->assertStringContainsString('fila 2', $messages);
        $this->assertStringContainsString('cantidad debe ser mayor a cero', $messages);
        $this->assertStringContainsString('tipo de detalle no es válido', $messages);
        $this->assertStringContainsString('no es un número', $messages);

        Queue::assertNothingPushed();
    }

    public function test_valida_los_datos_y_la_cookie_de_la_hoja_orden_compra(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_purchase_orders.upload']);

        $details = [['0', 'REF1-NEGRO-JEAN-30', 3, 5, 10000, '']];

        // Cookie incompleta
        $response = $this->upload($this->order($details, [
            ['cookie', 'tipo', 'fecha', 'proveedor'],
            ['TKNSGRDDREVENTCALZADOSAS=x', 23200, '2026-11-01', '900123456'],
        ]))->assertStatus(422);
        $this->assertStringContainsString('cookie de Siigo está incompleta', implode(' ', $response->json('errors.file')));

        // Falta el proveedor
        $response = $this->upload($this->order($details, [
            ['cookie', 'tipo', 'fecha', 'proveedor'],
            [$this->cookie(), 23200, '2026-11-01', ''],
        ]))->assertStatus(422);
        $this->assertStringContainsString('falta "proveedor"', implode(' ', $response->json('errors.file')));

        Queue::assertNothingPushed();
    }

    public function test_valida_el_archivo_el_correo_y_la_referencia(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_purchase_orders.upload']);

        $this->postJson(self::UPLOAD, [])->assertStatus(422)->assertJsonValidationErrors(['file', 'email', 'referencia']);
        $this->upload($this->order([['0', 'REF1-NEGRO-JEAN-30', 3, 5, 10000, '']]), ['email' => 'no-es-correo'])->assertStatus(422)->assertJsonValidationErrors('email');

        Queue::assertNothingPushed();
    }
}

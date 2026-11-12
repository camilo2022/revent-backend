<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Services\Siigo\AccountPayable\AccountPayableException;
use App\Services\Siigo\AccountPayable\SiigoAccountPayableService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

/** Cuentas por pagar en la API: permisos finos por ruta y validación profunda de pagos, anticipos y conciliaciones. */
class AccountPayableTest extends TestCase
{
    use DatabaseTransactions;

    private const BASE = '/api/integration/accounts_payable';

    /** Permisos de solo consulta: ver, pero no crear pagos, anticipos ni conciliaciones. */
    private const READ_ONLY = [
        'settings', 'providers', 'documents', 'payments', 'payments_weekly', 'payment_html',
    ];

    private function full(): User
    {
        return User::where('username', 'operaciones')->firstOrFail();
    }

    /** Un usuario de tienda al que solo se le dan los permisos de consulta. */
    private function readOnly(): User
    {
        $user = User::where('username', 'alegra')->firstOrFail();
        $user->givePermissionTo(array_map(fn ($action) => "integration.accounts_payable.{$action}", self::READ_ONLY));

        return $user->fresh();
    }

    private function service(callable $expectations): MockInterface
    {
        return $this->mock(SiigoAccountPayableService::class, $expectations);
    }

    private function proveedor(): array
    {
        return ['AccountID' => 123, 'MsThirdPartyID' => 'tercero-uuid-1'];
    }

    private function paymentBody(array $override = []): array
    {
        return array_replace([
            'proveedor' => $this->proveedor(),
            'tipo' => 1,
            'accion' => 'PAGO A PROVEEDOR',
            'origen' => 5,
            'fecha' => '2026-11-10',
            'valor' => 150.50,
            'observaciones' => 'Pago de prueba',
            'documentos' => [
                ['doc' => ['DueName' => 'FC-1', 'ACEntryID' => 10, 'Saldo' => 200], 'covered' => 100.50],
                ['doc' => ['DueName' => 'FC-2', 'ACEntryID' => 11, 'Saldo' => 50], 'covered' => 50],
            ],
        ], $override);
    }

    private function conciliationBody(array $override = []): array
    {
        return array_replace([
            'proveedor' => $this->proveedor(),
            'tipo' => 2,
            'fecha' => '2026-11-10',
            'observaciones' => 'Cruce de prueba',
            'documentos' => [
                ['doc' => ['DueName' => 'FC-1', 'ACEntryID' => 10], 'covered' => 80],
            ],
            'recibos' => [
                ['doc' => ['DueName' => 'RP-9'], 'used' => 80],
            ],
        ], $override);
    }

    // ------------------------------------------------------------------ Sesión y permisos

    public function test_sin_sesion_todas_las_rutas_responden_401(): void
    {
        $this->getJson(self::BASE . '/settings')->assertStatus(401);
        $this->getJson(self::BASE . '/providers')->assertStatus(401);
        $this->postJson(self::BASE . '/payment', $this->paymentBody())->assertStatus(401);
    }

    public function test_sin_permisos_de_cuentas_por_pagar_responde_403(): void
    {
        Sanctum::actingAs(User::where('username', 'alegra')->firstOrFail());

        $this->getJson(self::BASE . '/settings')->assertStatus(403);
        $this->getJson(self::BASE . '/providers')->assertStatus(403);
        $this->postJson(self::BASE . '/payment', $this->paymentBody())->assertStatus(403);
    }

    public function test_el_usuario_de_solo_consulta_ve_pero_no_crea_pagos_avances_ni_conciliaciones(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('screenData')->once()->andReturn(['type_receipt' => ['Code' => 1], 'bank_accounts' => [], 'type_documents' => []]);
            $mock->shouldReceive('providers')->once()->with(false)->andReturn([['MsThirdPartyID' => 'x']]);
            $mock->shouldReceive('registerPayment', 'registerAdvance', 'registerConciliation')->never();
        });

        Sanctum::actingAs($this->readOnly());

        $this->getJson(self::BASE . '/settings')->assertOk()->assertJsonPath('data.settings.type_receipt.Code', 1);
        $this->getJson(self::BASE . '/providers')->assertOk()->assertJsonPath('data.providers.0.MsThirdPartyID', 'x');

        $this->postJson(self::BASE . '/providers_sync')->assertStatus(403);
        $this->postJson(self::BASE . '/payment', $this->paymentBody())->assertStatus(403);
        $this->postJson(self::BASE . '/advance', ['proveedor' => $this->proveedor()])->assertStatus(403);
        $this->postJson(self::BASE . '/conciliation', $this->conciliationBody())->assertStatus(403);
    }

    public function test_las_lecturas_no_gastan_el_cupo_de_las_escrituras(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('screenData')->times(12)->andReturn(['type_receipt' => null, 'bank_accounts' => [], 'type_documents' => []]);
            $mock->shouldReceive('registerAdvance')->never();
        });
        Sanctum::actingAs($this->full());

        // Doce lecturas del grupo (límite 30) no pueden dejar sin cupo a las escrituras (límite 10 con prefijo propio)
        for ($i = 0; $i < 12; $i++) {
            $this->getJson(self::BASE . '/settings')->assertOk();
        }

        // Cada escritura cuenta una sola vez: diez pasan (aquí fallan en validación, 422) y la once se frena con 429
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(self::BASE . '/advance', [])->assertStatus(422);
        }
        $this->postJson(self::BASE . '/advance', [])->assertStatus(429);
    }

    // ------------------------------------------------------------------ Consultas (una ruta, una acción)

    public function test_documentos_y_pagos_salen_por_rutas_separadas(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('providerDocuments')->once()->with('tercero-uuid-1', 123, true)->andReturn([['DueName' => 'FC-1']]);
            $mock->shouldReceive('paymentsByMonth')->once()->with(123)->andReturn([['mes' => 'Noviembre 2026', 'total' => 10, 'detalles' => []]]);
        });

        Sanctum::actingAs($this->full());

        $this->getJson(self::BASE . '/documents/123?MsThirdPartyID=tercero-uuid-1&all_data=1')
            ->assertOk()
            ->assertJsonPath('data.documents.0.DueName', 'FC-1')
            ->assertJsonMissingPath('data.payments');

        $this->getJson(self::BASE . '/payments/123')
            ->assertOk()
            ->assertJsonPath('data.payments.0.mes', 'Noviembre 2026')
            ->assertJsonMissingPath('data.documents');
    }

    public function test_documentos_exige_el_tercero(): void
    {
        $this->service(fn (MockInterface $mock) => $mock->shouldReceive('providerDocuments')->never());
        Sanctum::actingAs($this->full());

        $this->getJson(self::BASE . '/documents/123')->assertStatus(422)->assertJsonValidationErrors('MsThirdPartyID');
    }

    public function test_la_sincronizacion_de_proveedores_es_su_propia_ruta(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('providers')->once()->with(true)->andReturn([]);
        });
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/providers_sync')->assertOk()->assertJsonPath('data.providers', []);
    }

    public function test_pagos_semanales_validan_el_rango(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('weeklyPayments')->once()->andReturn([['semana' => 1]]);
        });
        Sanctum::actingAs($this->full());

        $this->getJson(self::BASE . '/payments_weekly?date_from=2026-11-10&date_to=2026-11-01')
            ->assertStatus(422)->assertJsonValidationErrors('date_to');

        $this->getJson(self::BASE . '/payments_weekly?date_from=2026-11-01&date_to=2026-11-10')
            ->assertOk()
            ->assertJsonPath('data.weekly.desde', '2026-11-01')
            ->assertJsonPath('data.weekly.hasta', '2026-11-10')
            ->assertJsonPath('data.weekly.weeks.0.semana', 1);
    }

    public function test_el_html_del_comprobante_sale_dentro_del_json(): void
    {
        $this->service(fn (MockInterface $mock) => $mock->shouldReceive('paymentHtml')->once()->with(99)->andReturn('<p>Recibo 99</p>'));
        Sanctum::actingAs($this->full());

        $this->getJson(self::BASE . '/payment_html/99')->assertOk()->assertJsonPath('data.html', '<p>Recibo 99</p>');
    }

    // ------------------------------------------------------------------ Pagos

    public function test_registra_un_pago_con_los_arrays_validados(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('registerPayment')->once()->withArgs(function ($proveedor, $documentos, $data, $receipt) {
                return $proveedor['AccountID'] == 123
                    && count($documentos) === 2
                    && $documentos[0]['doc']['DueName'] === 'FC-1'
                    && $documentos[0]['doc']['Saldo'] == 200 // el documento completo llega al servicio (lo usa el correo)
                    && $data === [
                        'fecha' => '2026-11-10', 'tipo' => 1, 'accion' => 'PAGO A PROVEEDOR', 'origen' => 5,
                        'valor' => 150.5, 'observaciones' => 'Pago de prueba',
                    ]
                    && $receipt === null;
            })->andReturn(['voucher_id' => 7, 'voucher' => ['code' => 7]]);
        });
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/payment', $this->paymentBody())
            ->assertStatus(201)
            ->assertJsonPath('data.voucher.voucher_id', 7)
            ->assertJsonPath('message', 'Recibo de pago registrado correctamente.');
    }

    public function test_el_pago_acepta_multipart_con_comprobante(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('registerPayment')->once()->withArgs(function ($proveedor, $documentos, $data, $receipt) {
                return $receipt instanceof UploadedFile && $documentos[1]['doc']['ACEntryID'] == 11;
            })->andReturn(['voucher_id' => 8, 'voucher' => []]);
        });
        Sanctum::actingAs($this->full());

        $body = $this->paymentBody() + ['comprobante' => UploadedFile::fake()->create('comprobante.pdf', 20, 'application/pdf')];

        $this->post(self::BASE . '/payment', $body, ['Accept' => 'application/json'])->assertStatus(201);
    }

    public function test_el_pago_rechaza_documentos_vacios_o_mal_formados(): void
    {
        $this->service(fn (MockInterface $mock) => $mock->shouldReceive('registerPayment')->never());
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/payment', $this->paymentBody(['documentos' => []]))
            ->assertStatus(422)->assertJsonValidationErrors('documentos');

        $this->postJson(self::BASE . '/payment', $this->paymentBody(['documentos' => [['covered' => 150.50]]]))
            ->assertStatus(422)->assertJsonValidationErrors(['documentos.0.doc']);

        $this->postJson(self::BASE . '/payment', $this->paymentBody(['documentos' => [['doc' => ['DueName' => 'FC-1'], 'covered' => 150.50]]]))
            ->assertStatus(422)->assertJsonValidationErrors(['documentos.0.doc.ACEntryID']);
    }

    public function test_el_pago_rechaza_valores_aplicados_en_cero_o_negativos(): void
    {
        $this->service(fn (MockInterface $mock) => $mock->shouldReceive('registerPayment')->never());
        Sanctum::actingAs($this->full());

        foreach ([0, -5] as $covered) {
            $this->postJson(self::BASE . '/payment', $this->paymentBody([
                'valor' => 50,
                'documentos' => [['doc' => ['DueName' => 'FC-1', 'ACEntryID' => 10], 'covered' => $covered]],
            ]))->assertStatus(422)->assertJsonValidationErrors(['documentos.0.covered']);
        }
    }

    public function test_el_pago_exige_que_las_sumas_cuadren_y_que_no_haya_documentos_repetidos(): void
    {
        $this->service(fn (MockInterface $mock) => $mock->shouldReceive('registerPayment')->never());
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/payment', $this->paymentBody(['valor' => 999]))
            ->assertStatus(422)->assertJsonValidationErrors('valor');

        $this->postJson(self::BASE . '/payment', $this->paymentBody([
            'valor' => 100,
            'documentos' => [
                ['doc' => ['DueName' => 'FC-1', 'ACEntryID' => 10], 'covered' => 50],
                ['doc' => ['DueName' => 'FC-1', 'ACEntryID' => 10], 'covered' => 50],
            ],
        ]))->assertStatus(422)->assertJsonValidationErrors(['documentos.0.doc.DueName', 'documentos.1.doc.DueName']);
    }

    public function test_el_pago_exige_proveedor_y_datos_del_recibo(): void
    {
        $this->service(fn (MockInterface $mock) => $mock->shouldReceive('registerPayment')->never());
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/payment', ['documentos' => $this->paymentBody()['documentos']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['proveedor', 'tipo', 'accion', 'origen', 'fecha', 'valor']);

        $this->postJson(self::BASE . '/payment', $this->paymentBody(['proveedor' => ['AccountID' => 'abc']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['proveedor.AccountID', 'proveedor.MsThirdPartyID']);
    }

    public function test_un_error_de_negocio_del_servicio_responde_422_con_su_mensaje(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('registerPayment')->once()->andThrow(new AccountPayableException('No se encontraron documentos para el proveedor.', 404));
        });
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/payment', $this->paymentBody())
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'No se encontraron documentos para el proveedor.');
    }

    // ------------------------------------------------------------------ Anticipos

    public function test_registra_un_anticipo(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('registerAdvance')->once()->withArgs(function ($proveedor, $data, $receipt) {
                return $proveedor['MsThirdPartyID'] === 'tercero-uuid-1' && $data['valor'] == 300 && $receipt === null;
            })->andReturn(['voucher_id' => 5, 'voucher' => []]);
        });
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/advance', [
            'proveedor' => $this->proveedor(), 'tipo' => 1, 'accion' => 'ANTICIPO', 'origen' => 5,
            'fecha' => '2026-11-10', 'valor' => 300,
        ])->assertStatus(201)->assertJsonPath('message', 'Recibo de anticipo registrado correctamente.');
    }

    public function test_el_anticipo_exige_un_valor_mayor_a_cero(): void
    {
        $this->service(fn (MockInterface $mock) => $mock->shouldReceive('registerAdvance')->never());
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/advance', [
            'proveedor' => $this->proveedor(), 'tipo' => 1, 'accion' => 'ANTICIPO', 'origen' => 5,
            'fecha' => '2026-11-10', 'valor' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors('valor');
    }

    // ------------------------------------------------------------------ Conciliaciones

    public function test_registra_una_conciliacion(): void
    {
        $this->service(function (MockInterface $mock) {
            $mock->shouldReceive('registerConciliation')->once()->withArgs(function ($proveedor, $documentos, $recibos, $data) {
                return $documentos[0]['covered'] == 80
                    && $recibos[0]['used'] == 80
                    && $data === ['fecha' => '2026-11-10', 'tipo' => 2, 'observaciones' => 'Cruce de prueba'];
            })->andReturn(['voucher_id' => 3, 'voucher' => ['code' => 3]]);
        });
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/conciliation', $this->conciliationBody())
            ->assertStatus(201)
            ->assertJsonPath('data.voucher.voucher_id', 3);
    }

    public function test_la_conciliacion_exige_documentos_y_recibos_con_sumas_iguales(): void
    {
        $this->service(fn (MockInterface $mock) => $mock->shouldReceive('registerConciliation')->never());
        Sanctum::actingAs($this->full());

        $this->postJson(self::BASE . '/conciliation', $this->conciliationBody(['recibos' => [['doc' => ['DueName' => 'RP-9'], 'used' => 70]]]))
            ->assertStatus(422)->assertJsonValidationErrors('recibos');

        $this->postJson(self::BASE . '/conciliation', $this->conciliationBody(['recibos' => []]))
            ->assertStatus(422)->assertJsonValidationErrors('recibos');

        $this->postJson(self::BASE . '/conciliation', $this->conciliationBody(['documentos' => []]))
            ->assertStatus(422)->assertJsonValidationErrors('documentos');

        $this->postJson(self::BASE . '/conciliation', $this->conciliationBody(['recibos' => [['doc' => ['DueName' => 'RP-9'], 'used' => 0]]]))
            ->assertStatus(422)->assertJsonValidationErrors('recibos.0.used');
    }
}

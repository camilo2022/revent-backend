<?php

namespace Tests\Feature\Integration;

use App\Jobs\ImportMasiveTransferSiigoJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Traslados masivos: formato de Excel y carga validada que despacha el mismo job que Integration. */
class MasiveTransfersTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const FORMAT = '/api/integration/masive_transfers/format';

    private const UPLOAD = '/api/integration/masive_transfers/upload';

    private const WAREHOUSES = [
        ['id' => -1, 'name' => 'SIN ASIGNAR', 'transito' => false],
        ['id' => 3, 'name' => 'ALEGRA', 'transito' => true],
        ['id' => 9, 'name' => 'MAYALES', 'transito' => true],
    ];

    private function email(): string
    {
        return 'bodega@' . config('siigo.mail.domain');
    }

    /** Excel con la forma del formato: hoja traslado (una fila) y hoja traslado_detalles. */
    private function transferFile(array $details, ?array $transferRows = null)
    {
        return $this->xlsx([
            'traslado' => $transferRows ?? [['concepto', 'observaciones'], ['Traslado', 'Prueba']],
            'traslado_detalles' => [['codigo', 'bodega_salida', 'bodega_entrada', 'cantidad'], ...$details],
        ]);
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson(self::FORMAT)->assertStatus(401);
        $this->postJson(self::UPLOAD, [])->assertStatus(401);
    }

    public function test_cada_accion_exige_su_permiso(): void
    {
        Queue::fake();
        $this->fakeCatalog(self::WAREHOUSES);

        $this->actingWith(['integration.masive_transfers.upload']);
        $this->getJson(self::FORMAT)->assertStatus(403);

        $this->actingWith(['integration.masive_transfers.format']);
        $this->postJson(self::UPLOAD, [])->assertStatus(403);

        Queue::assertNothingPushed();
    }

    public function test_descarga_el_formato_en_excel(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->actingWith(['integration.masive_transfers.format']);

        $response = $this->get(self::FORMAT, ['Accept' => 'application/json'])->assertOk();

        $this->assertStringContainsString('formato_traslado.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_carga_un_traslado_valido_y_despacha_el_job_con_las_bodegas_como_id(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_transfers.upload']);

        $file = $this->transferFile([['PROD1', '3-ALEGRA', 70, 5], ['PROD2', 3, '70-TRANSITO ALEGRA', 2]]);

        $this->postJson(self::UPLOAD, ['file' => $file, 'email' => $this->email()])
            ->assertStatus(202)
            ->assertJsonPath('data.upload.email', $this->email())
            ->assertJsonPath('data.upload.lines', 2);

        Queue::assertPushedOn('masive-transfers', ImportMasiveTransferSiigoJob::class);
        Queue::assertPushed(ImportMasiveTransferSiigoJob::class, 1);
        Queue::assertPushed(ImportMasiveTransferSiigoJob::class, function ($job) {
            $transfer = $this->jobValue($job, 'transfer');
            $details = $transfer['traslado_detalles'];

            return $this->jobValue($job, 'email') === $this->email()
                && $transfer->has('traslado')
                && $details->count() === 2
                && $details[0]['codigo'] === 'PROD1'
                && $details[0]['bodega_salida'] === 3
                && $details[0]['bodega_entrada'] === 70
                && $details[1]['bodega_salida'] === 3
                && $details[1]['bodega_entrada'] === 70;
        });
    }

    public function test_rechaza_un_archivo_sin_las_hojas_del_formato(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_transfers.upload']);

        $file = $this->xlsx(['otra_hoja' => [['codigo'], ['X']]]);

        $this->postJson(self::UPLOAD, ['file' => $file, 'email' => $this->email()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        Queue::assertNothingPushed();
    }

    public function test_rechaza_columnas_faltantes_y_valores_vacios(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_transfers.upload']);

        $sinColumna = $this->xlsx([
            'traslado' => [['concepto'], ['x']],
            'traslado_detalles' => [['codigo', 'bodega_salida', 'cantidad'], ['PROD1', 3, 1]],
        ]);
        $this->postJson(self::UPLOAD, ['file' => $sinColumna, 'email' => $this->email()])
            ->assertStatus(422)->assertJsonValidationErrors('file');

        $vacios = $this->transferFile([['PROD1', 3, 70, null], ['', 3, 70, 1], ['PROD3', 3, 70, 'mucho']]);
        $response = $this->postJson(self::UPLOAD, ['file' => $vacios, 'email' => $this->email()])
            ->assertStatus(422)->assertJsonValidationErrors('file');

        $errors = implode(' | ', $response->json('errors.file'));
        $this->assertStringContainsString('fila 2', $errors);
        $this->assertStringContainsString('fila 3', $errors);
        $this->assertStringContainsString('fila 4', $errors);

        Queue::assertNothingPushed();
    }

    public function test_una_tienda_puede_enviar_a_otra_tienda(): void
    {
        Queue::fake();
        // alegra opera en las bodegas 3 y 70: la salida es suya y la entrada puede ser de otra tienda
        $this->actingWith(['integration.masive_transfers.upload']);

        $file = $this->transferFile([['PROD1', 3, 9, 1]]);

        $this->postJson(self::UPLOAD, ['file' => $file, 'email' => $this->email()])->assertStatus(202);
    }

    public function test_rechaza_sacar_mercancia_de_una_bodega_que_no_es_de_su_tienda(): void
    {
        Queue::fake();
        // alegra solo opera en las bodegas 3 y 70
        $this->actingWith(['integration.masive_transfers.upload']);

        $file = $this->transferFile([['PROD1', 9, 3, 1]]);

        $response = $this->postJson(self::UPLOAD, ['file' => $file, 'email' => $this->email()])
            ->assertStatus(422)->assertJsonValidationErrors('file');

        $this->assertStringContainsString('No tienes acceso a la bodega', implode(' ', $response->json('errors.file')));
        Queue::assertNothingPushed();
    }

    public function test_el_supervisor_puede_mover_entre_cualquier_bodega(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_transfers.upload', 'stores.access_all'], 'operaciones');

        $file = $this->transferFile([['PROD1', 3, 9, 1]]);

        $this->postJson(self::UPLOAD, ['file' => $file, 'email' => $this->email()])->assertStatus(202);

        Queue::assertPushed(ImportMasiveTransferSiigoJob::class, 1);
    }

    public function test_valida_el_correo_y_el_tipo_de_archivo(): void
    {
        Queue::fake();
        $this->actingWith(['integration.masive_transfers.upload']);

        $this->postJson(self::UPLOAD, ['email' => $this->email()])->assertStatus(422)->assertJsonValidationErrors('file');
        $this->postJson(self::UPLOAD, ['file' => $this->transferFile([['PROD1', 3, 70, 1]]), 'email' => 'alguien@gmail.com'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson(self::UPLOAD, ['file' => $this->transferFile([['PROD1', 3, 70, 1]])])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        Queue::assertNothingPushed();
    }
}

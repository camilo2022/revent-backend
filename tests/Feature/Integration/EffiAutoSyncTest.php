<?php

namespace Tests\Feature\Integration;

use App\Mail\EffiSyncManualRequiredMail;
use App\Mail\EffiSyncResultMail;
use App\Services\Effi\EffiAutoSync;
use App\Services\Effi\EffiInventorySyncPlanner;
use App\Services\Effi\EffiInventorySyncRunner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Sincronización automática tras una nota de traslado que toca la bodega WEB: con planificador y ejecutor simulados. */
class EffiAutoSyncTest extends TestCase
{
    private function plan(array $overrides = []): array
    {
        return $overrides + [
            'ok' => true,
            'bodega_effi' => 'Bodega / Principal',
            'totales' => ['unidades_siigo' => 10, 'unidades_effi' => 6],
            'decisiones' => ['categorias' => [], 'duplicados' => []],
            'manuales' => [],
            'items' => [
                ['codigo' => 'A1', 'descripcion' => 'AAA-NEGRO-X-BA-36', 'siigo' => 5.0, 'effi' => 2.0, 'avisos' => [], 'pasos' => [
                    ['tipo' => 'referencia', 'de' => 'AAAN36', 'a' => 'A1'], ['tipo' => 'ajuste', 'cantidad' => 3.0, 'costo' => 0.0],
                ]],
                ['codigo' => 'B2', 'descripcion' => 'BBB-BLANCO-X-BA-37', 'siigo' => 1.0, 'effi' => 4.0, 'avisos' => [], 'pasos' => [['tipo' => 'ajuste', 'cantidad' => -3.0, 'costo' => 0.0]]],
                ['codigo' => 'C3', 'descripcion' => 'CCC-CAFE-X-BA-38', 'siigo' => 4.0, 'effi' => null, 'avisos' => [], 'pasos' => [
                    ['tipo' => 'crear', 'datos' => []], ['tipo' => 'ajuste', 'cantidad' => 4.0, 'costo' => 0.0],
                ]],
            ],
        ];
    }

    private function auto(array $plan, ?array $result = null): EffiAutoSync
    {
        $this->mock(EffiInventorySyncPlanner::class, fn ($m) => $m->shouldReceive('plan')->andReturn($plan));
        $this->mock(EffiInventorySyncRunner::class, fn ($m) => $result === null
            ? $m->shouldNotReceive('run')
            : $m->shouldReceive('run')->once()->andReturn($result));

        return app(EffiAutoSync::class);
    }

    private function transfer(int $out, int $in, string $doc = 'NT-1'): array
    {
        return ['data' => ['document' => $doc, 'bodega_salida' => $out, 'bodega_ingreso' => $in]];
    }

    public function test_solo_las_notas_que_salen_de_la_bodega_web_o_entran_a_ella_disparan_la_sincronizacion(): void
    {
        config(['effi.siigo_warehouse' => 31]);
        $auto = app(EffiAutoSync::class);

        $docs = $auto->webDocuments([$this->transfer(31, 9, 'NT-1'), $this->transfer(9, 31, 'NT-2'), $this->transfer(9, 3, 'NT-3')]);

        $this->assertSame(['NT-1', 'NT-2'], array_column($docs, 'documento'));
        $this->assertSame(['Salida de la bodega WEB', 'Ingreso a la bodega WEB'], array_column($docs, 'sentido'));
    }

    public function test_apagada_no_hace_nada(): void
    {
        Mail::fake();
        config(['effi.sync_enabled' => true, 'effi.auto_sync' => false]);

        $this->mock(EffiInventorySyncPlanner::class, fn ($m) => $m->shouldNotReceive('plan'));
        app(EffiAutoSync::class)->afterTransfers([$this->transfer(31, 9)]);

        Mail::assertNothingSent();
    }

    public function test_con_decisiones_pendientes_no_sincroniza_y_avisa_una_sola_vez_al_correo_de_la_bodega_web(): void
    {
        Mail::fake();
        Cache::flush();
        config(['effi.sync_enabled' => true, 'effi.auto_sync' => true, 'effi.siigo_warehouse' => 31]);

        $plan = $this->plan(['decisiones' => ['categorias' => [['modelo' => 'BABUCHA', 'cantidad' => 1, 'ejemplos' => ['BB.33.38.1000'], 'sugerida' => null]], 'duplicados' => []]]);
        $auto = $this->auto($plan, null);
        $documents = [['documento' => 'NT-1', 'sentido' => 'Salida de la bodega WEB']];

        $this->assertSame('manual', $auto->run($documents));
        $this->assertSame('manual', $auto->run($documents)); // otra nota con lo mismo pendiente: no repite el correo

        Mail::assertSent(EffiSyncManualRequiredMail::class, 1);
        Mail::assertSent(EffiSyncManualRequiredMail::class, fn ($m) => $m->hasTo('paginawebrevent@gmail.com'));

        $html = (new EffiSyncManualRequiredMail($documents, $auto->pendingDecisions($plan)))->render();
        $this->assertStringContainsString('manualmente', $html);
        $this->assertStringContainsString('BABUCHA', $html);
        $this->assertStringContainsString('NT-1', $html);
    }

    public function test_sin_inconsistencias_sincroniza_sola_y_manda_la_confirmacion_explicada(): void
    {
        Mail::fake();
        Cache::flush();
        config(['effi.sync_enabled' => true, 'effi.auto_sync' => true, 'effi.siigo_warehouse' => 31]);

        $result = ['hechos' => ['referencias' => 1, 'creados' => 1, 'controles' => 0, 'vaciados' => 0, 'ingresos' => 2, 'egresos' => 1, 'anulados' => 0], 'errores' => []];
        $documents = [['documento' => 'NT-9', 'sentido' => 'Ingreso a la bodega WEB']];

        $this->assertSame('sincronizado', $this->auto($this->plan(), $result)->run($documents));

        Mail::assertSent(EffiSyncResultMail::class, fn ($m) => $m->hasTo('paginawebrevent@gmail.com'));
        Mail::assertNotSent(EffiSyncManualRequiredMail::class);
    }

    public function test_el_informe_cuenta_unidades_que_ingresaron_y_salieron_y_marca_lo_que_fallo(): void
    {
        $auto = $this->auto($this->plan());
        $result = ['hechos' => ['referencias' => 1, 'creados' => 1, 'controles' => 0, 'vaciados' => 0, 'ingresos' => 2, 'egresos' => 1, 'anulados' => 0], 'errores' => ['C3' => 'ajuste: Effi respondió 400']];

        $report = $auto->report($this->plan(), $result, [['documento' => 'NT-9', 'sentido' => 'Ingreso a la bodega WEB']]);

        // A1: 2 → 5 (+3), B2: 4 → 1 (−3), C3 falló: se queda en 0
        $this->assertSame(3.0, (float) $report['totales']['entradas']);
        $this->assertSame(3.0, (float) $report['totales']['salidas']);
        $this->assertSame(1, $report['totales']['con_error']);
        $this->assertSame(6.0, (float) $report['totales']['unidades_effi_despues']); // 6 antes + 3 − 3
        $this->assertSame('ajuste: Effi respondió 400', $report['lineas'][2]['error']);

        $mail = new EffiSyncResultMail($report);
        $html = $mail->render();

        $this->assertStringContainsString('NT-9', $html);
        $this->assertStringContainsString('Referencia cambiada: AAAN36 → A1', $html);
        $this->assertStringContainsString('No se pudieron sincronizar 1 código', $html);
        $this->assertStringContainsString('Ingreso a la bodega WEB', $html);
        $this->assertSame('sincronizacion_effi_bodega_web.csv', $mail->build()->rawAttachments[0]['name']);
        $this->assertStringContainsString('A1;AAA-NEGRO-X-BA-36;2;5;5', $mail->build()->rawAttachments[0]['data']);
    }

    public function test_la_sincronizacion_manual_corre_en_segundo_plano_y_avisa_por_correo_a_la_bodega_web(): void
    {
        Mail::fake();
        Cache::flush();
        config(['effi.siigo_warehouse' => 31, 'effi.queue' => 'sync-siigo-effi']);

        $job = new \App\Jobs\EffiInventorySyncJob(['modelos' => []]);
        $this->assertSame('sync-siigo-effi', $job->queue);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);

        $plan = $this->plan();
        $result = ['hechos' => ['referencias' => 1, 'creados' => 1, 'controles' => 0, 'vaciados' => 0, 'ingresos' => 2, 'egresos' => 1, 'anulados' => 0], 'errores' => []];
        $planner = \Mockery::mock(EffiInventorySyncPlanner::class);
        $planner->shouldReceive('plan')->andReturn($plan);
        $planner->shouldReceive('applyDecisions')->andReturnArg(0);
        $runner = \Mockery::mock(EffiInventorySyncRunner::class);
        $runner->shouldReceive('run')->once()->andReturn($result);

        $job->handle($planner, $runner, app(\App\Services\Effi\EffiClient::class), app(EffiAutoSync::class));

        Mail::assertSent(EffiSyncResultMail::class, fn ($m) => $m->hasTo('paginawebrevent@gmail.com') && $m->report['manual'] === true);
        $this->assertSame('terminado', Cache::get(\App\Jobs\EffiInventorySyncJob::STATUS_KEY)['estado']);
        $this->assertStringContainsString('lanzó desde la pantalla', (new EffiSyncResultMail(app(EffiAutoSync::class)->report($plan, $result, [], manual: true)))->render());
    }

    public function test_si_la_sincronizacion_manual_falla_avisa_por_correo_a_la_bodega_web(): void
    {
        Mail::fake();
        Cache::flush();
        config(['effi.siigo_warehouse' => 31]);

        $planner = \Mockery::mock(EffiInventorySyncPlanner::class);
        $planner->shouldReceive('plan')->andReturn(['ok' => false, 'message' => 'Effi no entrega el stock']);
        $runner = \Mockery::mock(EffiInventorySyncRunner::class);
        $runner->shouldNotReceive('run');

        (new \App\Jobs\EffiInventorySyncJob())->handle($planner, $runner, app(\App\Services\Effi\EffiClient::class), app(EffiAutoSync::class));

        Mail::assertSent(\App\Mail\EffiSyncFailedMail::class, fn ($m) => $m->hasTo('paginawebrevent@gmail.com') && str_contains($m->render(), 'Effi no entrega el stock'));
        $this->assertSame('error', Cache::get(\App\Jobs\EffiInventorySyncJob::STATUS_KEY)['estado']);
    }

    public function test_si_ya_esta_igual_no_manda_nada(): void
    {
        Mail::fake();
        Cache::flush();
        config(['effi.sync_enabled' => true, 'effi.auto_sync' => true]);

        $this->assertSame('sin_cambios', $this->auto($this->plan(['items' => []]), null)->run([]));

        Mail::assertNothingSent();
    }
}

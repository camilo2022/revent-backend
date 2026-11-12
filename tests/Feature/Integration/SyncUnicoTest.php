<?php

namespace Tests\Feature\Integration;

use App\Jobs\SyncUnicoSiigoJob;
use App\Services\Siigo\SyncUnicoRunLog;
use App\Support\Integration\LegacyCacheKeys;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Sincronización manual con Único: historial y lanzamiento, con el mismo job y el mismo bloqueo que Integration. */
class SyncUnicoTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const RUNS = '/api/integration/sync_unico/runs';

    private const RUN = '/api/integration/sync_unico/run';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(LegacyCacheKeys::syncUnicoManualLock());
        Cache::forget('SIIGO:SYNC-UNICO:RUNS');
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson(self::RUNS)->assertStatus(401);
        $this->postJson(self::RUN, ['mode' => 'today'])->assertStatus(401);
    }

    public function test_cada_accion_exige_su_permiso(): void
    {
        Queue::fake();

        $this->actingWith(['integration.sync_unico.runs']);
        $this->getJson(self::RUNS)->assertOk();
        $this->postJson(self::RUN, ['mode' => 'today'])->assertStatus(403);

        $this->actingWith(['integration.sync_unico.run']);
        $this->getJson(self::RUNS)->assertStatus(403);

        Queue::assertNothingPushed();
    }

    public function test_el_historial_trae_las_corridas_y_el_correo_de_aviso(): void
    {
        $this->actingWith(['integration.sync_unico.runs']);

        $this->getJson(self::RUNS)
            ->assertOk()
            ->assertJsonPath('data.runs', [])
            ->assertJsonPath('data.notify_email', config('siigo.mail.technology'));

        $runId = app(SyncUnicoRunLog::class)->start(SyncUnicoRunLog::MANUAL, '2026-11-10 00:00:00', '2026-11-10 23:59:59');

        $this->getJson(self::RUNS)
            ->assertOk()
            ->assertJsonCount(1, 'data.runs')
            ->assertJsonPath('data.runs.0.run_id', $runId)
            ->assertJsonPath('data.runs.0.source', SyncUnicoRunLog::MANUAL)
            ->assertJsonPath('data.runs.0.status', 'running');
    }

    public function test_lanza_el_job_con_los_mismos_parametros_que_la_pantalla_de_integration(): void
    {
        Carbon::setTestNow('2026-11-11 15:30:00');
        Queue::fake();
        $this->actingWith(['integration.sync_unico.run', 'integration.sync_unico.runs']);

        $response = $this->postJson(self::RUN, ['mode' => 'yesterday'])
            ->assertStatus(202)
            ->assertJsonPath('data.run.created_start', '2026-11-10 00:00:00')
            ->assertJsonPath('data.run.created_end', '2026-11-10 23:59:59');

        $runId = $response->json('data.run.run_id');

        Queue::assertPushedOn('sync-unico', SyncUnicoSiigoJob::class);
        Queue::assertPushed(SyncUnicoSiigoJob::class, 1);
        Queue::assertPushed(SyncUnicoSiigoJob::class, fn ($job) => $job->params === [
            'created_start' => '2026-11-10 00:00:00',
            'created_end' => '2026-11-10 23:59:59',
            'page' => 1,
            'page_size' => 100,
            'source' => SyncUnicoRunLog::MANUAL,
            'run_id' => $runId,
        ]);

        // Queda registrada en el historial
        $this->getJson(self::RUNS)->assertJsonPath('data.runs.0.run_id', $runId);

        Carbon::setTestNow();
    }

    public function test_un_segundo_lanzamiento_seguido_responde_429_con_el_bloqueo_de_integration(): void
    {
        Queue::fake();
        $this->actingWith(['integration.sync_unico.run']);

        $this->postJson(self::RUN, ['mode' => 'today'])->assertStatus(202);
        $this->postJson(self::RUN, ['mode' => 'today'])->assertStatus(429);

        Queue::assertPushed(SyncUnicoSiigoJob::class, 1);

        // El bloqueo es el mismo texto que usa la pantalla de Integration
        $this->assertTrue(Cache::has('SIIGO:SYNC-UNICO:MANUAL-LOCK'));
    }

    public function test_si_la_pantalla_de_integration_ya_bloqueo_la_api_no_lanza_otra(): void
    {
        Queue::fake();
        Cache::add('SIIGO:SYNC-UNICO:MANUAL-LOCK', true, 60);
        $this->actingWith(['integration.sync_unico.run']);

        $this->postJson(self::RUN, ['mode' => 'today'])->assertStatus(429);

        Queue::assertNothingPushed();
    }

    public function test_valida_el_periodo_y_el_maximo_de_dias(): void
    {
        Queue::fake();
        $this->actingWith(['integration.sync_unico.run']);

        $this->postJson(self::RUN, [])->assertStatus(422)->assertJsonValidationErrors('mode');
        $this->postJson(self::RUN, ['mode' => 'semana'])->assertStatus(422)->assertJsonValidationErrors('mode');
        $this->postJson(self::RUN, ['mode' => 'range'])->assertStatus(422)->assertJsonValidationErrors(['created_start', 'created_end']);
        $this->postJson(self::RUN, ['mode' => 'range', 'created_start' => '2026-11-10', 'created_end' => '2026-11-01'])
            ->assertStatus(422)->assertJsonValidationErrors('created_end');
        $this->postJson(self::RUN, ['mode' => 'range', 'created_start' => '2026-01-01', 'created_end' => '2026-03-01'])
            ->assertStatus(422)->assertJsonValidationErrors('created_end');

        $this->postJson(self::RUN, ['mode' => 'range', 'created_start' => '2026-10-01', 'created_end' => '2026-10-31'])
            ->assertStatus(202);

        Queue::assertPushed(SyncUnicoSiigoJob::class, 1);
        Queue::assertPushed(SyncUnicoSiigoJob::class, fn ($job) => $job->params['created_start'] === '2026-10-01 00:00:00'
            && $job->params['created_end'] === '2026-10-31 23:59:59');
    }
}

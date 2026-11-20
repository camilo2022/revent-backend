<?php

namespace Tests\Feature\Integration;

use App\Jobs\EffiInventorySyncJob;
use App\Services\Effi\EffiInventorySyncPlanner;
use App\Services\Effi\EffiWebInventoryComparator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Sincronización con Effi en la API: permisos por acción, consultas simuladas y el mismo job de la pantalla web. */
class EffiSyncApiTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const BASE = '/api/integration/effi_sync';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson(self::BASE . '/compare')->assertStatus(401);
    }

    public function test_cada_accion_exige_su_permiso(): void
    {
        $this->actingWith([]);

        $this->getJson(self::BASE . '/compare')->assertStatus(403);
        $this->getJson(self::BASE . '/plan')->assertStatus(403);
        $this->getJson(self::BASE . '/status')->assertStatus(403);
        $this->postJson(self::BASE . '/sync', ['confirmacion' => 'SINCRONIZAR'])->assertStatus(403);
    }

    public function test_el_cuadre_entrega_lo_mismo_que_el_servicio(): void
    {
        $this->actingWith(['integration.effi_sync.compare']);
        $this->mock(EffiWebInventoryComparator::class, fn ($m) => $m->shouldReceive('compare')->andReturn(['resumen' => ['unidades_siigo' => 5], 'filas' => []]));

        $this->getJson(self::BASE . '/compare')->assertOk()->assertJsonPath('data.comparison.resumen.unidades_siigo', 5);
    }

    public function test_un_plan_que_no_se_puede_armar_es_422_con_el_motivo(): void
    {
        $this->actingWith(['integration.effi_sync.plan']);
        $this->mock(EffiInventorySyncPlanner::class, fn ($m) => $m->shouldReceive('plan')->andReturn(['ok' => false, 'message' => 'Effi no entrega el stock']));

        $this->getJson(self::BASE . '/plan')->assertStatus(422)->assertJsonPath('error.message', 'Effi no entrega el stock');
    }

    public function test_con_la_sincronizacion_apagada_no_despacha_nada(): void
    {
        $this->actingWith(['integration.effi_sync.sync']);
        config(['effi.sync_enabled' => false]);
        Queue::fake();

        $this->postJson(self::BASE . '/sync', ['confirmacion' => 'SINCRONIZAR'])->assertStatus(422);
        Queue::assertNothingPushed();
    }

    public function test_exige_la_confirmacion_escrita(): void
    {
        $this->actingWith(['integration.effi_sync.sync']);
        config(['effi.sync_enabled' => true]);
        Queue::fake();

        $this->postJson(self::BASE . '/sync', ['confirmacion' => 'si'])->assertStatus(422)->assertJsonValidationErrors('confirmacion');
        Queue::assertNothingPushed();
    }

    public function test_despacha_el_mismo_job_con_las_decisiones_y_el_codigo_de_prueba(): void
    {
        $this->actingWith(['integration.effi_sync.sync']);
        config(['effi.sync_enabled' => true]);
        Queue::fake();

        $this->postJson(self::BASE . '/sync', [
            'confirmacion' => 'SINCRONIZAR',
            'solo' => 'BA.10.35.1012',
            'decisiones' => ['modelos' => ['SEBA' => ['accion' => 'existente', 'categoria' => 1]], 'duplicados' => ['X1' => ['inhabilitar' => '10']]],
        ])->assertStatus(202);

        Queue::assertPushedOn(config('effi.queue'), EffiInventorySyncJob::class, function ($job) {
            return $this->jobValue($job, 'only') === 'BA.10.35.1012'
                && $this->jobValue($job, 'decisions')['modelos']['SEBA'] === ['accion' => 'existente', 'categoria' => 1]
                && $this->jobValue($job, 'decisions')['duplicados']['X1'] === ['inhabilitar' => '10'];
        });
        $this->assertSame('planeando', Cache::get(EffiInventorySyncJob::STATUS_KEY)['estado']);
    }

    public function test_si_hay_una_sincronizacion_en_curso_responde_409(): void
    {
        $this->actingWith(['integration.effi_sync.sync', 'integration.effi_sync.status']);
        config(['effi.sync_enabled' => true]);
        Queue::fake();
        Cache::put(EffiInventorySyncJob::STATUS_KEY, ['estado' => 'ejecutando', 'fase' => 'Ajustes'], 60);

        $this->postJson(self::BASE . '/sync', ['confirmacion' => 'SINCRONIZAR'])->assertStatus(409);
        Queue::assertNothingPushed();
        $this->getJson(self::BASE . '/status')->assertOk()->assertJsonPath('data.status.fase', 'Ajustes');
    }
}

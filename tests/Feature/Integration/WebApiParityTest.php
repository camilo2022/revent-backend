<?php

namespace Tests\Feature\Integration;

use App\Jobs\ExportInventorySiigoJob;
use App\Jobs\ExportInvoiceSiigoJob;
use App\Jobs\SyncUnicoSiigoJob;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/**
 * Prueba liviana de equivalencia: la misma acción lanzada desde la web (routes/web.php) y desde la API del front
 * debe despachar el mismo job, con los mismos parámetros y en la misma cola. Nada llega a Siigo (cola y caché simulados).
 */
class WebApiParityTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        // El historial y el bloqueo de la sync usan caché: en esta prueba se quedan en memoria
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    /** Despacha por la web y por la API la misma petición y devuelve los jobs de cada una: [web, api]. */
    private function runBoth(string $job, string $webUrl, string $apiUrl, array $body, string $permission): array
    {
        $this->actingWith([$permission]);

        Queue::fake();
        // La web responde con una vista o, al lanzar la sync, con una redirección: ambas son éxito
        $status = $this->withoutMiddleware(VerifyCsrfToken::class)->post($webUrl, $body)->getStatusCode();
        $this->assertTrue($status >= 200 && $status < 400, "La web respondió {$status}");
        $web = Queue::pushed($job)->values();

        Queue::fake();
        Cache::flush();
        $this->postJson($apiUrl, $body)->assertStatus(202);
        $api = Queue::pushed($job)->values();

        return [$web, $api];
    }

    public function test_exporte_de_facturas_despacha_lo_mismo_desde_la_web_y_la_api(): void
    {
        [$web, $api] = $this->runBoth(
            ExportInvoiceSiigoJob::class,
            '/siigo/export_invoice_download',
            '/api/integration/exports/invoice',
            ['month' => '2026-10'],
            'integration.exports_invoice.send'
        );

        $this->assertCount(1, $web);
        $this->assertCount(1, $api);
        $this->assertSame($this->jobValue($web[0], 'filters'), $this->jobValue($api[0], 'filters'));
        $this->assertEquals($this->jobValue($web[0], 'notifyEmail'), $this->jobValue($api[0], 'notifyEmail'));
        $this->assertSame($web[0]->queue, $api[0]->queue);
    }

    public function test_exporte_de_inventario_despacha_lo_mismo_desde_la_web_y_la_api(): void
    {
        [$web, $api] = $this->runBoth(
            ExportInventorySiigoJob::class,
            '/siigo/export_inventory_download',
            '/api/integration/exports/inventory',
            [],
            'integration.exports_inventory.send'
        );

        $this->assertCount(2, $web); // positivo y negativo
        $this->assertSame($web->map(fn ($job) => $this->jobValue($job, 'filters'))->all(), $api->map(fn ($job) => $this->jobValue($job, 'filters'))->all());
        $this->assertEquals($this->jobValue($web[0], 'notifyEmail'), $this->jobValue($api[0], 'notifyEmail'));
        $this->assertSame($web[0]->queue, $api[0]->queue);
    }

    public function test_la_sincronizacion_con_unico_despacha_lo_mismo_desde_la_web_y_la_api(): void
    {
        [$web, $api] = $this->runBoth(
            SyncUnicoSiigoJob::class,
            '/siigo/sync_unico/manual',
            '/api/integration/sync_unico/run',
            ['mode' => 'yesterday'],
            'integration.sync_unico.run'
        );

        $this->assertCount(1, $web);
        $this->assertCount(1, $api);

        // Todo igual salvo el id de la corrida, que es distinto en cada lanzamiento
        $strip = fn ($job) => collect($this->jobValue($job, 'params'))->except('run_id')->all();
        $this->assertSame($strip($web[0]), $strip($api[0]));
        $this->assertSame($web[0]->queue, $api[0]->queue);
    }
}

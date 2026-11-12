<?php

namespace Tests\Feature\Integration;

use App\Jobs\ExportInventorySiigoJob;
use App\Jobs\ExportInvoice360SiigoJob;
use App\Jobs\ExportInvoiceSiigoJob;
use App\Jobs\ExportPurchaseSiigoJob;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Exportes por correo (inventario, facturas, facturas 360 y compras): mismo job y mismos parámetros que Integration. */
class ExportsTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    /** ruta => [permiso, job] */
    private const EXPORTS = [
        'inventory' => ['integration.exports_inventory.send', ExportInventorySiigoJob::class],
        'invoice' => ['integration.exports_invoice.send', ExportInvoiceSiigoJob::class],
        'invoice_360' => ['integration.exports_invoice_360.send', ExportInvoice360SiigoJob::class],
        'purchase' => ['integration.exports_purchase.send', ExportPurchaseSiigoJob::class],
    ];

    private function url(string $export): string
    {
        return "/api/integration/exports/{$export}";
    }

    public function test_sin_sesion_responde_401_en_los_cuatro_exportes(): void
    {
        foreach (array_keys(self::EXPORTS) as $export) {
            $this->postJson($this->url($export), [])->assertStatus(401);
        }
    }

    public function test_cada_exporte_exige_su_propio_permiso(): void
    {
        Queue::fake();

        foreach (self::EXPORTS as $export => [$permission]) {
            // Con el permiso de otro exporte no puede
            $other = collect(self::EXPORTS)->first(fn ($item) => $item[0] !== $permission)[0];
            $this->actingWith([$other]);

            $this->postJson($this->url($export), [])->assertStatus(403);
        }

        Queue::assertNothingPushed();
    }

    public function test_inventario_sin_tipo_despacha_positivo_y_negativo_con_los_filtros_de_siempre(): void
    {
        Queue::fake();
        $this->actingWith(['integration.exports_inventory.send']);

        $this->postJson($this->url('inventory'), [])
            ->assertStatus(202)
            ->assertJsonPath('data.export.jobs', 2)
            ->assertJsonPath('data.export.emails', config('siigo.mail.inventory'));

        Queue::assertPushed(ExportInventorySiigoJob::class, 2);
        Queue::assertPushedOn('reports', ExportInventorySiigoJob::class);

        $filters = Queue::pushed(ExportInventorySiigoJob::class)->map(fn ($job) => $this->jobValue($job, 'filters'))->values();
        $this->assertSame(['page_size' => 100, 'type' => 'Product', 'positive' => true], $filters[0]);
        $this->assertSame(['page_size' => 100, 'type' => 'Product', 'positive' => false], $filters[1]);

        Queue::pushed(ExportInventorySiigoJob::class)->each(
            fn ($job) => $this->assertEquals(config('siigo.mail.inventory'), $this->jobValue($job, 'notifyEmail'))
        );
    }

    public function test_inventario_con_tipo_despacha_un_solo_job_con_sus_correos(): void
    {
        Queue::fake();
        $this->actingWith(['integration.exports_inventory.send']);
        $email = 'bodega@' . config('siigo.mail.domain');

        $this->postJson($this->url('inventory'), [
            'inventory_type' => 'negativo', 'page_size' => 50, 'type' => 'Variant', 'emails' => [$email],
        ])->assertStatus(202)->assertJsonPath('data.export.jobs', 1);

        Queue::assertPushed(ExportInventorySiigoJob::class, 1);
        Queue::assertPushed(ExportInventorySiigoJob::class, function ($job) use ($email) {
            return $this->jobValue($job, 'filters') === ['page_size' => 50, 'type' => 'Variant', 'positive' => false]
                && $this->jobValue($job, 'notifyEmail') === [$email];
        });
    }

    public function test_facturas_facturas_360_y_compras_despachan_su_job_con_el_rango_del_mes(): void
    {
        Carbon::setTestNow('2026-11-11 10:00:00');

        foreach (['invoice', 'invoice_360', 'purchase'] as $export) {
            Queue::fake();
            [$permission, $job] = self::EXPORTS[$export];
            $this->actingWith([$permission]);

            $this->postJson($this->url($export), ['month' => '2026-03'])
                ->assertStatus(202)
                ->assertJsonPath('data.export.jobs', 1)
                ->assertJsonPath('data.export.emails', config('siigo.mail.exports'));

            Queue::assertPushed($job, function ($pushed) {
                return $pushed->filters === ['created_start' => '2026-03-01 00:00:00', 'created_end' => '2026-03-31 23:59:59', 'page_size' => 100]
                    && $pushed->notifyEmail == config('siigo.mail.exports');
            });
            Queue::assertPushedOn('reports', $job);
        }

        Carbon::setTestNow();
    }

    public function test_el_rango_manual_tiene_prioridad_sobre_el_mes_y_sin_datos_usa_el_mes_en_curso(): void
    {
        Carbon::setTestNow('2026-11-11 10:00:00');
        Queue::fake();
        $this->actingWith(['integration.exports_invoice.send']);
        $email = 'ventas@' . config('siigo.mail.domain');

        $this->postJson($this->url('invoice'), [
            'month' => '2026-01', 'created_start' => '2026-02-05', 'created_end' => '2026-02-10', 'page_size' => 200, 'emails' => [$email],
        ])->assertStatus(202);
        $this->postJson($this->url('invoice'), [])->assertStatus(202);

        $pushed = Queue::pushed(ExportInvoiceSiigoJob::class)->values();
        $this->assertSame(['created_start' => '2026-02-05 00:00:00', 'created_end' => '2026-02-10 00:00:00', 'page_size' => 200], $pushed[0]->filters);
        $this->assertSame([$email], $pushed[0]->notifyEmail);
        $this->assertSame(['created_start' => '2026-11-01 00:00:00', 'created_end' => '2026-11-30 23:59:59', 'page_size' => 100], $pushed[1]->filters);

        Carbon::setTestNow();
    }

    public function test_valida_correos_fechas_y_tamanos(): void
    {
        Queue::fake();
        $this->actingWith(['integration.exports_invoice.send', 'integration.exports_inventory.send']);

        $this->postJson($this->url('invoice'), ['emails' => ['alguien@gmail.com']])
            ->assertStatus(422)->assertJsonValidationErrors('emails.0');
        $this->postJson($this->url('invoice'), ['emails' => []])
            ->assertStatus(422)->assertJsonValidationErrors('emails');
        $this->postJson($this->url('invoice'), ['created_start' => '2026-02-05'])
            ->assertStatus(422)->assertJsonValidationErrors('created_end');
        $this->postJson($this->url('invoice'), ['created_start' => '2026-02-10', 'created_end' => '2026-02-05'])
            ->assertStatus(422)->assertJsonValidationErrors('created_end');
        $this->postJson($this->url('invoice'), ['month' => '2026/03'])
            ->assertStatus(422)->assertJsonValidationErrors('month');
        $this->postJson($this->url('invoice'), ['page_size' => 5000])
            ->assertStatus(422)->assertJsonValidationErrors('page_size');
        $this->postJson($this->url('inventory'), ['inventory_type' => 'neutro', 'type' => 'Otro'])
            ->assertStatus(422)->assertJsonValidationErrors(['inventory_type', 'type']);

        Queue::assertNothingPushed();
    }
}

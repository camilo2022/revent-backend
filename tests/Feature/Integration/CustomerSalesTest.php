<?php

namespace Tests\Feature\Integration;

use App\Services\Siigo\SiigoCustomerSalesService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Clientes por venta: el token solo valida al usuario de Siigo; los datos salen con el token de la API. */
class CustomerSalesTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const COST_CENTERS = '/api/integration/customer_sales/cost_centers';

    private const SEARCH = '/api/integration/customer_sales/search';

    private function customers(): array
    {
        return [[
            'identification' => '900123',
            'uuid' => 'uuid-secreto',
            'name' => 'CLIENTE UNO',
            'email' => 'cliente@example.com',
            'phone' => '3001112233',
            'phone_valid' => true,
            'invoices' => [[
                'name' => 'FV-1-100',
                'date' => '2026-11-10',
                'total' => 150000,
                'items' => [['code' => 'JEAN1', 'description' => 'Jean', 'quantity' => 2]],
            ]],
        ]];
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson(self::COST_CENTERS)->assertStatus(401);
        $this->postJson(self::SEARCH, [])->assertStatus(401);
    }

    public function test_cada_accion_exige_su_permiso(): void
    {
        $this->fakeSiigoToken();
        $this->fakeSiigoUser();

        $this->actingWith(['integration.customer_sales.cost_centers']);
        $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'date' => '2026-11-10'])->assertStatus(403);

        $this->actingWith(['integration.customer_sales.search']);
        $this->getJson(self::COST_CENTERS)->assertStatus(403);
    }

    public function test_lista_los_centros_de_costo(): void
    {
        $this->fakeSiigoToken();
        $this->mock(SiigoCustomerSalesService::class, fn ($mock) => $mock->shouldReceive('costCenters')->once()->andReturn([
            ['id' => 11, 'code' => 'ALE', 'name' => 'ALEGRA', 'active' => true],
            ['id' => 12, 'code' => 'MAY', 'name' => 'MAYALES'],
        ]));
        $this->actingWith(['integration.customer_sales.cost_centers']);

        $this->getJson(self::COST_CENTERS)
            ->assertOk()
            ->assertJsonPath('data.cost_centers', [
                ['id' => 11, 'code' => 'ALE', 'name' => 'ALEGRA'],
                ['id' => 12, 'code' => 'MAY', 'name' => 'MAYALES'],
            ]);
    }

    public function test_si_siigo_falla_los_centros_de_costo_responden_422_con_mensaje(): void
    {
        $this->fakeSiigoToken();
        $this->mock(SiigoCustomerSalesService::class, fn ($mock) => $mock->shouldReceive('costCenters')->andThrow(new \Exception('detalle interno de Siigo')));
        $this->actingWith(['integration.customer_sales.cost_centers']);

        $response = $this->getJson(self::COST_CENTERS)->assertStatus(422);

        $this->assertSame('No se pudieron cargar los centros de costo.', $response->json('error.message'));
        $this->assertStringNotContainsString('detalle interno', $response->getContent());
    }

    public function test_consulta_los_clientes_de_un_dia_y_centro_de_costo(): void
    {
        $this->fakeSiigoToken();
        $this->fakeSiigoUser();
        $this->mock(SiigoCustomerSalesService::class, fn ($mock) => $mock->shouldReceive('search')
            ->once()
            ->with('token-api-de-prueba', '2026-11-10', '2026-11-10', 12)
            ->andReturn($this->customers()));
        $this->actingWith(['integration.customer_sales.search']);

        $response = $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'date' => '2026-11-10', 'cost_center' => 12])->assertOk();

        $response->assertJsonPath('data.customers.0.identification', '900123')
            ->assertJsonPath('data.customers.0.name', 'CLIENTE UNO')
            ->assertJsonPath('data.customers.0.phone_valid', true)
            ->assertJsonPath('data.customers.0.invoices.0.name', 'FV-1-100')
            ->assertJsonPath('data.customers.0.invoices.0.items.0.code', 'JEAN1');

        // Ni el token del usuario ni el uuid interno del cliente salen en la respuesta
        $this->assertStringNotContainsString($this->jwt(), $response->getContent());
        $this->assertStringNotContainsString('uuid-secreto', $response->getContent());
    }

    public function test_sin_centro_de_costo_consulta_todos(): void
    {
        $this->fakeSiigoToken();
        $this->fakeSiigoUser();
        $this->mock(SiigoCustomerSalesService::class, fn ($mock) => $mock->shouldReceive('search')
            ->once()
            ->with('token-api-de-prueba', '2026-11-10', '2026-11-10', null)
            ->andReturn([]));
        $this->actingWith(['integration.customer_sales.search']);

        $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'date' => '2026-11-10'])
            ->assertOk()
            ->assertJsonPath('data.customers', []);
    }

    public function test_un_token_invalido_responde_422_y_nunca_401(): void
    {
        $this->fakeSiigoToken();
        $this->fakeSiigoUser(false);
        $this->mock(SiigoCustomerSalesService::class, fn ($mock) => $mock->shouldReceive('search')->never());
        $this->actingWith(['integration.customer_sales.search']);

        $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'date' => '2026-11-10'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');

        // Sin la forma de un JWT ni siquiera se consulta a Siigo
        $this->postJson(self::SEARCH, ['token' => 'cualquier-cosa', 'date' => '2026-11-10'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');
    }

    public function test_valida_token_fecha_y_centro_de_costo(): void
    {
        $this->actingWith(['integration.customer_sales.search']);

        $this->postJson(self::SEARCH, [])->assertStatus(422)->assertJsonValidationErrors(['token', 'date']);
        $this->fakeSiigoToken();
        $this->fakeSiigoUser();
        $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'date' => '10/11/2026', 'cost_center' => 'abc'])
            ->assertStatus(422)->assertJsonValidationErrors(['date', 'cost_center']);
    }

    public function test_si_siigo_falla_al_consultar_responde_422_sin_detalles_internos(): void
    {
        $this->fakeSiigoToken();
        $this->fakeSiigoUser();
        $this->mock(SiigoCustomerSalesService::class, fn ($mock) => $mock->shouldReceive('search')->andThrow(new \Exception('cuerpo crudo de Siigo')));
        $this->actingWith(['integration.customer_sales.search']);

        $response = $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'date' => '2026-11-10'])->assertStatus(422);

        $this->assertStringNotContainsString('cuerpo crudo', $response->getContent());
    }
}

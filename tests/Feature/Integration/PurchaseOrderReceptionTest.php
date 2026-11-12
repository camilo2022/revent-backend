<?php

namespace Tests\Feature\Integration;

use App\Mail\InvoicePurchaseOrderConfirmedSiigoMail;
use App\Services\Integration\PurchaseOrderItemsParser;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoDocumentService;
use App\Services\SiigoPurchaseOrderCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Recepción de órdenes de compra: búsqueda con acceso por bodega y confirmación con evidencias y correo. */
class PurchaseOrderReceptionTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const SEARCH = '/api/integration/purchase_order_reception/search';

    private const CONFIRM = '/api/integration/purchase_order_reception/confirm';

    /** Detalle de una orden en Siigo con un ítem en la bodega 3 (Alegra) y otro en la 9 (Mayales). */
    private function detail(): array
    {
        return [
            'Entry' => ['ACEntryID' => 123],
            'Items' => [
                ['ProductCode' => 'P-1', 'LongDescription' => 'REF1-NEGRO-JEAN-30', 'WarehouseCode' => 3, 'Quantity' => 5, 'UnitValue' => 1000],
                ['ProductCode' => 'P-2', 'LongDescription' => 'REF1-NEGRO-JEAN-32', 'WarehouseCode' => 9, 'Quantity' => 2, 'UnitValue' => 1000],
            ],
        ];
    }

    private function fakeSiigoOrder(?array $quicksearch, ?array $detail = null, bool $expectFetch = true): void
    {
        $this->fakeSiigoToken();
        $this->fakeSiigoUser();

        $this->mock(SiigoDocumentService::class, function ($mock) use ($quicksearch) {
            $mock->shouldReceive('quicksearch')->andReturn($quicksearch);
        });

        $this->mock(SiigoCatalogService::class, function ($mock) {
            $mock->shouldReceive('warehousesById')->andReturn(collect([3 => ['name' => 'ALEGRA'], 9 => ['name' => 'MAYALES']]));
        });

        $this->mock(SiigoPurchaseOrderCacheService::class, function ($mock) use ($detail, $expectFetch) {
            $expectation = $mock->shouldReceive('fetchDetail')
                ->with('token-api-de-prueba', 123, 'OC-ID-123') // misma clave de caché que usa Integration
                ->andReturn($detail);

            $expectFetch ? $expectation->once() : $expectation->never();
        });
    }

    private function found(): array
    {
        return [['codeEntity' => 'OC-1-100', 'urlEntity' => 'https://siigo.test/Doc?ERPDocumentID=123']];
    }

    private function receiving(array $overrides = []): array
    {
        return array_replace_recursive([
            'Order' => ['ACEntryID' => 123, 'DocName' => 'OC-1-100', 'DocDate' => '2026-11-01', 'Warehouse' => '3 - ALEGRA'],
            'Items' => [
                ['ProductCode' => 'P-1', 'WarehouseCode' => 3, 'Quantity' => 5, 'ReceivingQuantity' => 5, 'PendingQuantity' => 0],
            ],
            'Totals' => ['Requested' => 5, 'Receiving' => 5, 'Pending' => 0],
            'Checklist' => [
                'cajas_master' => true, 'sellos' => true, 'estado_cajas' => true, 'documentos' => true, 'empaque' => true,
                'stickers' => true, 'producto' => true, 'exactitud' => true, 'variacion_diseno' => false, 'firma' => true,
                'reporte_whatsapp' => true, 'respaldo_whatsapp' => true, 'alerta' => false, 'venta' => false,
                'referencias_recibidas' => 'REF1',
                'observaciones' => 'Todo bien',
                'responsable' => ['nombre' => 'Ana', 'cargo' => 'Auxiliar', 'departamento' => 'Bodega', 'celular' => '3000000000'],
            ],
        ], $overrides);
    }

    private function confirmBody(array $receiving, int $images = 1): array
    {
        $files = [];
        for ($i = 0; $i < $images; $i++) {
            $files[] = UploadedFile::fake()->image("evidencia{$i}.jpg");
        }

        return ['token' => $this->jwt(), 'receiving' => $receiving, 'imagenes' => $files];
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->postJson(self::SEARCH, [])->assertStatus(401);
        $this->postJson(self::CONFIRM, [])->assertStatus(401);
    }

    public function test_cada_accion_exige_su_permiso(): void
    {
        $this->actingWith(['integration.purchase_order_reception.confirm']);
        $this->postJson(self::SEARCH, [])->assertStatus(403);

        $this->actingWith(['integration.purchase_order_reception.search']);
        $this->postJson(self::CONFIRM, [])->assertStatus(403);
    }

    public function test_un_token_invalido_es_un_422_nunca_un_401(): void
    {
        $this->fakeSiigoUser(false);
        $this->actingWith(['integration.purchase_order_reception.search']);

        $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'query' => 'OC-1-100'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');

        $this->postJson(self::SEARCH, ['token' => 'no-es-un-jwt', 'query' => 'OC-1-100'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');
    }

    public function test_buscar_marca_cada_item_segun_las_bodegas_del_usuario(): void
    {
        $this->fakeSiigoOrder($this->found(), $this->detail());
        $this->actingWith(['integration.purchase_order_reception.search']);

        $order = $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'query' => 'OC-1-100'])
            ->assertOk()
            ->json('data.invoice_purchase_order');

        $this->assertSame([true, false], collect($order['Items'])->pluck('Accessible')->all());
        $this->assertFalse($order['accessible']);
        $this->assertSame('3 - ALEGRA', $order['Items'][0]['Warehouse']);
    }

    public function test_quien_tiene_acceso_a_todas_las_bodegas_ve_la_orden_completa(): void
    {
        $this->fakeSiigoOrder($this->found(), $this->detail());
        $this->actingWith(['integration.purchase_order_reception.search', 'stores.access_all'], 'operaciones');

        $order = $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'query' => 'OC-1-100'])
            ->assertOk()
            ->json('data.invoice_purchase_order');

        $this->assertTrue($order['accessible']);
        $this->assertSame([true, true], collect($order['Items'])->pluck('Accessible')->all());
    }

    public function test_una_orden_que_no_existe_responde_null(): void
    {
        $this->fakeSiigoOrder([], null, false);
        $this->actingWith(['integration.purchase_order_reception.search']);

        $this->postJson(self::SEARCH, ['token' => $this->jwt(), 'query' => 'OC-1-999'])
            ->assertOk()
            ->assertJsonPath('data.invoice_purchase_order', null);
    }

    public function test_confirmar_guarda_las_evidencias_y_envia_el_correo_con_los_datos_completos(): void
    {
        Storage::fake('public');
        Mail::fake();
        $this->fakeSiigoUser();
        $this->actingWith(['integration.purchase_order_reception.confirm']);

        $this->post(self::CONFIRM, $this->confirmBody($this->receiving(), 2), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.reception.images', 2);

        $this->assertCount(2, Storage::disk('public')->allFiles('evidences'));

        Mail::assertSent(InvoicePurchaseOrderConfirmedSiigoMail::class, function ($mail) {
            return $mail->data['Order']['DocName'] === 'OC-1-100'
                && $mail->data['Items'][0]['ProductCode'] === 'P-1'
                && $mail->data['Checklist']['responsable']['nombre'] === 'Ana'
                && $mail->usuario['data']['name'] === 'Operaciones'
                && count($mail->imagenes) === 2;
        });
    }

    public function test_confirmar_acepta_los_booleanos_que_envia_un_formulario(): void
    {
        Storage::fake('public');
        Mail::fake();
        $this->fakeSiigoUser();
        $this->actingWith(['integration.purchase_order_reception.confirm']);

        $receiving = $this->receiving();
        $receiving['Checklist']['sellos'] = 'true';
        $receiving['Checklist']['alerta'] = 'false';

        $this->post(self::CONFIRM, $this->confirmBody($receiving), ['Accept' => 'application/json'])->assertOk();

        Mail::assertSent(InvoicePurchaseOrderConfirmedSiigoMail::class, fn ($mail) => $mail->data['Checklist']['sellos'] === true && $mail->data['Checklist']['alerta'] === false);
    }

    public function test_confirmar_con_una_bodega_sin_acceso_responde_422_y_no_envia_nada(): void
    {
        Storage::fake('public');
        Mail::fake();
        $this->fakeSiigoUser();
        $this->actingWith(['integration.purchase_order_reception.confirm']);

        $receiving = $this->receiving(['Items' => [['WarehouseCode' => 9]]]);

        $this->post(self::CONFIRM, $this->confirmBody($receiving), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('receiving.Items.0.WarehouseCode');

        Mail::assertNothingSent();
        $this->assertSame([], Storage::disk('public')->allFiles('evidences'));
    }

    public function test_confirmar_valida_la_lista_de_verificacion_y_las_imagenes(): void
    {
        Storage::fake('public');
        Mail::fake();
        $this->fakeSiigoUser();
        $this->actingWith(['integration.purchase_order_reception.confirm']);

        $receiving = $this->receiving();
        unset($receiving['Checklist']['firma'], $receiving['Checklist']['responsable']['nombre']);

        $this->post(self::CONFIRM, $this->confirmBody($receiving, 0), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['receiving.Checklist.firma', 'receiving.Checklist.responsable.nombre', 'imagenes']);

        Mail::assertNothingSent();
    }

    public function test_confirmar_con_un_token_invalido_responde_422_sin_guardar_nada(): void
    {
        Storage::fake('public');
        Mail::fake();
        $this->fakeSiigoUser(false);
        $this->actingWith(['integration.purchase_order_reception.confirm']);

        $this->post(self::CONFIRM, $this->confirmBody($this->receiving()), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');

        $this->assertSame([], Storage::disk('public')->allFiles('evidences'));
        Mail::assertNothingSent();
    }

    public function test_el_parser_arma_los_items_igual_que_el_legacy(): void
    {
        $items = app(PurchaseOrderItemsParser::class)->parse(
            ['Items' => [
                ['ProductCode' => 'P-1', 'LongDescription' => 'REF1-NEGRO-JEAN-30', 'WarehouseCode' => 3, 'Quantity' => 5, 'UnitValue' => 1000, 'TaxAddValue' => 190, 'TaxDiscValue' => 40],
                ['ProductCode' => '', 'LongDescription' => 'sin código'],
                ['ProductCode' => 'P-3', 'LongDescription' => 'ESPECIAL'],
            ]],
            collect([3 => ['name' => 'ALEGRA']])
        );

        $this->assertCount(2, $items);
        $this->assertSame('REF1', $items[0]['Reference']);
        $this->assertSame('NEGRO', $items[0]['Color']);
        $this->assertSame('JEAN', $items[0]['Category']);
        $this->assertSame('30', $items[0]['Size']);
        $this->assertSame('3 - ALEGRA', $items[0]['Warehouse']);
        $this->assertSame(1150, $items[0]['UnitValue']);
        $this->assertNull($items[1]['Reference']);
        $this->assertNull($items[1]['Warehouse']);
    }
}

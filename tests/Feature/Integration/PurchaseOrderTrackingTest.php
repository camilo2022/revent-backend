<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Services\Integration\PurchaseOrderTrackingService;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoLinks;
use App\Services\SiigoProductsCache;
use App\Services\SiigoPurchaseOrderCacheService;
use App\Support\Integration\LegacyCacheKeys;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Seguimiento de órdenes de compra: lee las mismas claves de caché que Integration y filtra por las bodegas del usuario. */
class PurchaseOrderTrackingTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const DOCUMENTS = '/api/integration/purchase_order_tracking/documents';

    private const MISSING = '/api/integration/purchase_order_tracking/missing_download';

    /**
     * Siembra el caché como lo deja `siigo:warm-purchase-cache`: el listado de órdenes de ayer, el detalle de cada una
     * (OC-ID-{id}) y el proveedor. Las órdenes: 1001 en la bodega 3 (Alegra), 1002 en la 9 (Mayales) y
     * 1003 en la 3 pero con un número de la lista de excluidas.
     */
    private function seedCache(): void
    {
        $orders = [
            ['ACEntryID' => 1001, 'DocName' => 'OC-1-100', 'DocDate' => Carbon::yesterday()->toDateString(), 'FullName' => 'proveedor uno', 'MsThirdPartyID' => 'uuid-1', 'Identification' => '900111'],
            ['ACEntryID' => 1002, 'DocName' => 'OC-1-101', 'DocDate' => Carbon::yesterday()->toDateString(), 'FullName' => 'proveedor dos', 'MsThirdPartyID' => 'uuid-1', 'Identification' => '900222'],
            ['ACEntryID' => 1003, 'DocName' => 'OC-1-11589', 'DocDate' => Carbon::yesterday()->toDateString(), 'FullName' => 'proveedor tres', 'MsThirdPartyID' => 'uuid-1', 'Identification' => '900333'],
        ];

        Cache::put(LegacyCacheKeys::purchaseDay('OC', Carbon::yesterday()->toDateString()), $orders, 3600);

        foreach ([1001 => 3, 1002 => 9, 1003 => 3] as $id => $warehouse) {
            Cache::put(LegacyCacheKeys::purchaseDetail('OC', $id), [
                'Entry' => ['Observations' => 'FECHA LIMITE: 2026-12-01', 'SalesmanCode' => 7],
                'Items' => [[
                    'ProductCode' => "P-{$id}",
                    'LongDescription' => 'REF1-NEGRO-JEAN-30',
                    'WarehouseCode' => $warehouse,
                    'Quantity' => 5,
                    'UnitValue' => 1000,
                ]],
            ], 3600);
        }

        Cache::put(LegacyCacheKeys::provider('uuid-1'), ['BasicData' => ['CompanyName' => 'Proveedor SAS'], 'Comments' => 'PT jeans'], 3600);
    }

    /** Siigo y los catálogos que consulta el seguimiento, simulados (no sale a la red). */
    private function fakeSiigo(): void
    {
        $this->fakeSiigoToken();

        $this->mock(SiigoCatalogService::class, function ($mock) {
            $mock->shouldReceive('usersById')->andReturn(collect([7 => ['first_name' => 'Luis', 'last_name' => 'Perez']]));
            $mock->shouldReceive('warehousesById')->andReturn(collect([3 => ['name' => 'ALEGRA'], 9 => ['name' => 'MAYALES']]));
        });

        $this->mock(SiigoProductsCache::class, function ($mock) {
            $mock->shouldReceive('keyedByProductId')->andReturn(collect(['P-1001' => ['model' => 'JEAN']]));
        });

        $this->mock(SiigoPurchaseOrderCacheService::class, function ($mock) {
            $mock->shouldReceive('purchaseInvoices')->andReturn(collect());
            $mock->shouldReceive('purchaseOrders')->andReturn(collect());
            $mock->shouldReceive('fetchDetail')->never();
        });
    }

    private function orders(string $username, array $permissions): array
    {
        $this->actingWith($permissions, $username);

        return $this->getJson(self::DOCUMENTS)->assertOk()->json('data.purchase_orders');
    }

    public function test_sin_sesion_responde_401(): void
    {
        $this->getJson(self::DOCUMENTS)->assertStatus(401);
        $this->getJson(self::MISSING)->assertStatus(401);
    }

    public function test_cada_ruta_exige_su_permiso(): void
    {
        $this->actingWith(['integration.purchase_order_tracking.missing_download']);
        $this->getJson(self::DOCUMENTS)->assertStatus(403);

        $this->actingWith(['integration.purchase_order_tracking.documents']);
        $this->getJson(self::MISSING)->assertStatus(403);
    }

    public function test_las_claves_de_cache_son_las_del_legacy(): void
    {
        $this->assertSame('OC-ID-1001', LegacyCacheKeys::purchaseDetail('OC', 1001));
        $this->assertSame('FC-ID-55', LegacyCacheKeys::purchaseDetail('FC', 55));
        $this->assertSame('uuid-1', LegacyCacheKeys::provider('uuid-1'));
        $this->assertSame(SiigoPurchaseOrderCacheService::dayKey('OC', '2026-11-10'), LegacyCacheKeys::purchaseDay('OC', '2026-11-10'));
    }

    public function test_un_usuario_de_tienda_solo_ve_las_ordenes_de_sus_bodegas(): void
    {
        $this->seedCache();
        $this->fakeSiigo();

        $orders = $this->orders('alegra', ['integration.purchase_order_tracking.documents']);

        $this->assertSame(['OC-1-100', 'OC-1-11589'], collect($orders)->pluck('DocName')->all());
    }

    public function test_quien_tiene_acceso_a_todas_las_bodegas_ve_todas_las_ordenes(): void
    {
        $this->seedCache();
        $this->fakeSiigo();

        $orders = $this->orders('operaciones', ['integration.purchase_order_tracking.documents', 'stores.access_all']);

        $this->assertSame(['OC-1-100', 'OC-1-101', 'OC-1-11589'], collect($orders)->pluck('DocName')->all());
    }

    public function test_cada_orden_trae_el_seguimiento_del_legacy_y_las_bodegas(): void
    {
        $this->seedCache();
        $this->fakeSiigo();

        $order = $this->orders('alegra', ['integration.purchase_order_tracking.documents'])[0];

        $this->assertSame('PROVEEDOR UNO', $order['FullName']);
        $this->assertSame('PROVEEDOR SAS', $order['CompanyName']);
        $this->assertSame('PRODUCTO TERMINADO', $order['TypeProvider']);
        $this->assertSame('2026-12-01', $order['DeadlineDate']);
        $this->assertSame('age-green', $order['AgingStatus']);
        $this->assertSame('Luis Perez', $order['User']);
        $this->assertSame('3 - ALEGRA', $order['Warehouse']);
        $this->assertSame([3], $order['WarehouseCodes']);
        $this->assertEquals(5, $order['TotalQuantity']);
        $this->assertEquals(0, $order['TotalConfirmed']);
        $this->assertSame('REF1', $order['Items'][0]['Reference']);
        $this->assertEquals(-5, $order['Items'][0]['Pending']);
        $this->assertSame('JEAN', $order['Items'][0]['Model']);
        $this->assertSame(SiigoLinks::purchaseOrder(1001), $order['Link']);
    }

    public function test_las_8_ordenes_agregadas_al_final_estan_en_la_lista_de_excluidas(): void
    {
        $this->assertCount(count(array_unique(PurchaseOrderTrackingService::EXCLUDED_DOC_NAMES)), PurchaseOrderTrackingService::EXCLUDED_DOC_NAMES);

        foreach (['11589', '11590', '13611', '11600', '11585', '11586', '13615', '12627'] as $number) {
            $this->assertContains("OC-1-{$number}", PurchaseOrderTrackingService::EXCLUDED_DOC_NAMES);
        }
    }

    public function test_las_referencias_faltantes_omiten_las_ordenes_excluidas(): void
    {
        $this->seedCache();
        $this->fakeSiigo();

        $operaciones = User::where('username', 'operaciones')->firstOrFail();
        $operaciones->syncPermissions(['stores.access_all']);

        $rows = app(PurchaseOrderTrackingService::class)->missingRows($operaciones->fresh());

        $this->assertSame(['OC-1-100', 'OC-1-101'], collect($rows)->pluck('DocName')->unique()->values()->all());
        $this->assertSame('REF1', $rows[0]['Reference']);
        $this->assertSame('NEGRO', $rows[0]['Color']);
        $this->assertEquals(5, $rows[0]['Pending']);
    }

    public function test_las_referencias_faltantes_se_descargan_en_excel(): void
    {
        $this->seedCache();
        $this->fakeSiigo();
        $this->actingWith(['integration.purchase_order_tracking.missing_download']);

        $response = $this->get(self::MISSING, ['Accept' => 'application/json'])->assertOk();

        $this->assertStringContainsString('referencias_faltantes_', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }
}

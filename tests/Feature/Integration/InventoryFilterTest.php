<?php

namespace Tests\Feature\Integration;

use App\Services\Integration\InventoryFilterService;
use App\Services\ProductPhotoService;
use App\Services\SiigoProductsCache;
use App\Support\Integration\LegacyCacheKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** Filtro de inventario: bodegas filtradas por las tiendas del usuario y claves de caché compartidas con Integration. */
class InventoryFilterTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const BASE = '/api/integration/inventory_filter';

    private const WAREHOUSES = [
        ['id' => 3, 'name' => 'ALEGRA', 'transito' => true],
        ['id' => 9, 'name' => 'MAYALES', 'transito' => true],
    ];

    private function payload(): array
    {
        return [
            'referencia' => 'REF1',
            'productos' => [
                ['id' => 'REF1@3', 'referencia' => 'REF1', 'bodega' => 'ALEGRA', 'bodega_id' => 3],
                ['id' => 'REF1@9', 'referencia' => 'REF1', 'bodega' => 'MAYALES', 'bodega_id' => 9],
            ],
            'errores' => [],
        ];
    }

    public function test_sin_sesion_responde_401(): void
    {
        foreach (['catalogs', 'search', 'all_warehouses', 'images'] as $action) {
            $this->getJson(self::BASE . '/' . $action)->assertStatus(401);
        }
    }

    public function test_cada_ruta_exige_su_permiso(): void
    {
        $this->actingWith(['integration.inventory_filter.catalogs']);

        $this->getJson(self::BASE . '/search?warehouse_id=3')->assertStatus(403);
        $this->getJson(self::BASE . '/all_warehouses?referencia=REF1')->assertStatus(403);
        $this->getJson(self::BASE . '/images?referencia=REF1')->assertStatus(403);

        $this->actingWith(['integration.inventory_filter.search']);
        $this->getJson(self::BASE . '/catalogs')->assertStatus(403);
    }

    public function test_las_bodegas_del_catalogo_se_filtran_por_las_tiendas_del_usuario(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);

        $this->actingWith(['integration.inventory_filter.catalogs']);
        $ids = collect($this->getJson(self::BASE . '/catalogs')->assertOk()->json('data.catalogs.warehouses'))->pluck('id')->all();
        $this->assertSame([3], $ids);

        $this->actingWith(['integration.inventory_filter.catalogs', 'stores.access_all'], 'operaciones');
        $ids = collect($this->getJson(self::BASE . '/catalogs')->assertOk()->json('data.catalogs.warehouses'))->pluck('id')->all();
        $this->assertSame([3, 9], $ids);
    }

    public function test_el_catalogo_trae_los_grupos_de_color(): void
    {
        $this->fakeCatalog(self::WAREHOUSES);
        $this->actingWith(['integration.inventory_filter.catalogs']);

        $groups = $this->getJson(self::BASE . '/catalogs')->assertOk()->json('data.catalogs.color_groups');

        $this->assertNotEmpty($groups);
        $this->assertArrayHasKey('macrocategoria', $groups[0]);
        $this->assertArrayHasKey('colores', $groups[0]);
    }

    public function test_buscar_en_una_bodega_sin_acceso_responde_422(): void
    {
        $this->actingWith(['integration.inventory_filter.search']);

        $this->getJson(self::BASE . '/search?warehouse_id=9')
            ->assertStatus(422)
            ->assertJsonValidationErrors('warehouse_id');

        $this->getJson(self::BASE . '/search')->assertStatus(422)->assertJsonValidationErrors('warehouse_id');
    }

    public function test_buscar_en_una_bodega_con_acceso_entrega_los_productos(): void
    {
        $this->mock(InventoryFilterService::class, function ($mock) {
            $mock->shouldReceive('warehouseStock')->once()->with(3)->andReturn([['referencia' => 'REF1']]);
        });

        $this->actingWith(['integration.inventory_filter.search']);

        $this->getJson(self::BASE . '/search?warehouse_id=3')
            ->assertOk()
            ->assertJsonPath('data.inventory.productos.0.referencia', 'REF1');
    }

    public function test_todas_las_bodegas_usa_la_clave_de_cache_del_legacy_y_filtra_por_acceso(): void
    {
        $key = 'INVENTORY_FILTER:ALL_WAREHOUSES:v3:REF1';
        $this->assertSame($key, LegacyCacheKeys::inventoryAllWarehouses('REF1'));

        Cache::put($key, $this->payload(), 60);

        $this->actingWith(['integration.inventory_filter.all_warehouses']);
        $products = $this->getJson(self::BASE . '/all_warehouses?referencia=ref1')->assertOk()->json('data.inventory.productos');
        $this->assertSame([3], collect($products)->pluck('bodega_id')->all());

        $this->actingWith(['integration.inventory_filter.all_warehouses', 'stores.access_all'], 'operaciones');
        $products = $this->getJson(self::BASE . '/all_warehouses?referencia=REF1')->assertOk()->json('data.inventory.productos');
        $this->assertSame([3, 9], collect($products)->pluck('bodega_id')->all());

        // El payload guardado en el caché (que comparte la web) sigue completo
        $this->assertCount(2, Cache::get($key)['productos']);
    }

    public function test_todas_las_bodegas_valida_la_referencia(): void
    {
        $this->actingWith(['integration.inventory_filter.all_warehouses']);

        $this->getJson(self::BASE . '/all_warehouses')->assertStatus(422)->assertJsonValidationErrors('referencia');
        $this->getJson(self::BASE . '/all_warehouses?referencia=' . str_repeat('A', 101))->assertStatus(422)->assertJsonValidationErrors('referencia');
    }

    public function test_las_fotos_se_paginan_y_limitan_a_12_por_pagina(): void
    {
        Storage::fake(ProductPhotoService::DISK);
        foreach (['a.jpg', 'b.jpg', 'c.jpg'] as $name) {
            Storage::disk(ProductPhotoService::DISK)->put(ProductPhotoService::BASE_PATH . "/REF1/{$name}", 'x');
        }

        $this->actingWith(['integration.inventory_filter.images']);

        $page = $this->getJson(self::BASE . '/images?referencia=ref1&per_page=2&page=2')->assertOk()->json('data.images');
        $this->assertSame(3, $page['total']);
        $this->assertSame(2, $page['last_page']);
        $this->assertSame(2, $page['page']);
        $this->assertCount(1, $page['images']);
        $this->assertTrue($page['has_previous']);
        $this->assertFalse($page['has_next']);

        // Las fotos quedan en la clave de caché del legacy
        $this->assertTrue(Cache::has(LegacyCacheKeys::inventoryImages('REF1')));
        $this->assertSame('INVENTORY_FILTER:IMAGES:REF1', LegacyCacheKeys::inventoryImages('ref1'));

        $this->getJson(self::BASE . '/images?referencia=REF1&per_page=13')->assertStatus(422)->assertJsonValidationErrors('per_page');
    }

    public function test_el_servicio_agrupa_el_stock_por_referencia_color_y_talla(): void
    {
        Storage::fake(ProductPhotoService::DISK);

        $this->mock(SiigoProductsCache::class, function ($mock) {
            $mock->shouldReceive('keyedByProductId')->andReturn(collect([
                'P-1' => ['model' => 'JEAN', 'price' => 99000],
            ]));
        });

        $rows = [
            ['Description' => 'REF1-NEGRO-JEAN-HOMBRE-30', 'QuantityBalance' => 4, 'productcode' => 'P-1'],
            ['Description' => 'REF1-NEGRO-JEAN-HOMBRE-30', 'QuantityBalance' => 2, 'productcode' => 'P-1'],
            ['Description' => 'REF1-NEGRO-JEAN-HOMBRE-32', 'QuantityBalance' => 1, 'productcode' => 'P-1'],
            ['Description' => 'INVALIDO', 'QuantityBalance' => 9],
        ];

        $products = app(InventoryFilterService::class)->mapProducts($rows);

        $this->assertCount(1, $products);
        $this->assertSame('REF1', $products[0]['referencia']);
        $this->assertSame('JEAN', $products[0]['categoria']);
        $this->assertSame(99000, $products[0]['precio']);
        $this->assertSame(['30' => 6, '32' => 1], $products[0]['colores'][0]['tallas']);
    }
}

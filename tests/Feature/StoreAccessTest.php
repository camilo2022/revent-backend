<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\IntegrationNavigationSeeder;
use Database\Seeders\IntegrationPermissionsSeeder;
use Database\Seeders\StoreAccessPermissionsSeeder;
use Database\Seeders\StoreWarehousesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Tiendas = bodegas de Siigo y acceso por usuario (sobre la base ya sembrada con StoreWarehousesSeeder). */
class StoreAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('username', 'revent')->firstOrFail();
    }

    private function storeBody(array $override = []): array
    {
        return array_merge([
            'code' => 'TST',
            'name' => 'TIENDA DE PRUEBA',
            'location_type' => Country::class,
            'location_id' => Country::where('iso3', 'COL')->firstOrFail()->id,
            'address' => 'Por definir',
            'neighborhood' => 'Por definir',
        ], $override);
    }

    public function test_la_bodega_de_siigo_no_se_repite_entre_tiendas(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/stores/store', $this->storeBody(['siigo_warehouse_id' => 3]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('siigo_warehouse_id');

        $this->postJson('/api/stores/store', $this->storeBody(['siigo_warehouse_id' => 999001, 'siigo_transit_warehouse_id' => 70]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('siigo_transit_warehouse_id');

        $this->postJson('/api/stores/store', $this->storeBody(['siigo_warehouse_id' => 999001, 'siigo_transit_warehouse_id' => 999001]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('siigo_transit_warehouse_id');
    }

    public function test_crea_una_tienda_con_su_bodega_y_codigo_corto(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/stores/store', $this->storeBody(['siigo_warehouse_id' => 999001, 'siigo_transit_warehouse_id' => 999002]))
            ->assertStatus(201)
            ->assertJsonPath('data.store.siigo_warehouse_id', 999001)
            ->assertJsonPath('data.store.siigo_transit_warehouse_id', 999002);

        $this->postJson('/api/stores/store', $this->storeBody(['code' => 'DEMASIADO', 'name' => 'OTRA TIENDA']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_asignar_y_quitar_tiendas_a_un_usuario(): void
    {
        Sanctum::actingAs($this->admin());

        $alegra = User::where('username', 'alegra')->firstOrFail();
        $mayales = Store::where('siigo_warehouse_id', 9)->firstOrFail();

        $this->assertFalse($alegra->fresh()->canAccessWarehouse(9));

        $this->postJson("/api/users/stores/assign/{$alegra->id}", ['store_id' => $mayales->id])
            ->assertOk()
            ->assertJsonFragment(['id' => $mayales->id]);
        $this->assertTrue($alegra->fresh()->canAccessWarehouse(9));

        // No se puede asignar dos veces
        $this->postJson("/api/users/stores/assign/{$alegra->id}", ['store_id' => $mayales->id])->assertStatus(422);

        $this->getJson("/api/users/stores/all/{$alegra->id}")->assertOk()->assertJsonPath('data.access_all', false);

        $this->postJson("/api/users/stores/remove/{$alegra->id}", ['store_id' => $mayales->id])->assertOk();
        $this->assertFalse($alegra->fresh()->canAccessWarehouse(9));

        // Quitar una tienda que no tiene asignada
        $this->postJson("/api/users/stores/remove/{$alegra->id}", ['store_id' => $mayales->id])->assertStatus(422);
    }

    public function test_sin_permiso_no_puede_asignar_tiendas(): void
    {
        $alegra = User::where('username', 'alegra')->firstOrFail();
        $mayales = Store::where('siigo_warehouse_id', 9)->firstOrFail();

        $this->postJson("/api/users/stores/assign/{$alegra->id}", ['store_id' => $mayales->id])->assertStatus(401);

        Sanctum::actingAs($alegra);
        $this->postJson("/api/users/stores/assign/{$alegra->id}", ['store_id' => $mayales->id])->assertStatus(403);
        $this->getJson('/api/stores/siigo_warehouses')->assertStatus(403);
    }

    public function test_acceso_a_bodegas_con_y_sin_acceso_a_todas(): void
    {
        $alegra = User::where('username', 'alegra')->firstOrFail();
        $this->assertTrue($alegra->canAccessWarehouse(3), 'su bodega');
        $this->assertTrue($alegra->canAccessWarehouse(70), 'su tránsito');
        $this->assertFalse($alegra->canAccessWarehouse(9), 'otra bodega');
        $this->assertFalse($alegra->hasAccessToAllStores());
        $this->assertSame([3, 70], $alegra->accessibleWarehouseIds());

        $operaciones = User::where('username', 'operaciones')->firstOrFail();
        $this->assertTrue($operaciones->hasAccessToAllStores());
        $this->assertTrue($operaciones->canAccessWarehouse(9));
        $this->assertTrue($operaciones->canAccessWarehouse(-1));
        $this->assertSame(['*'], $operaciones->accessibleWarehouseIds());
    }

    public function test_el_usuario_de_la_tienda_trae_sus_tiendas_en_auth_user(): void
    {
        Sanctum::actingAs(User::where('username', 'alegra')->firstOrFail());

        $this->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('data.user.access_all', false)
            ->assertJsonPath('data.user.stores.0.siigo_warehouse_id', 3);
    }

    public function test_los_seeders_son_idempotentes(): void
    {
        $count = fn () => [
            Store::withTrashed()->count(), User::withTrashed()->count(), Person::withTrashed()->count(),
            Permission::count(), \DB::table('model_has_stores')->count(), \DB::table('submodules')->count(),
        ];

        $before = $count();

        foreach ([StoreAccessPermissionsSeeder::class, IntegrationPermissionsSeeder::class, StoreWarehousesSeeder::class, IntegrationNavigationSeeder::class] as $seeder) {
            $this->seed($seeder);
        }

        $this->assertSame($before, $count());
    }
}

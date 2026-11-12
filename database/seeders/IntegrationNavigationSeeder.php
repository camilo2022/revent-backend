<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Submodule;
use Illuminate\Database\Seeder;

/**
 * Módulo "Integraciones" con un submódulo por cada integración de Siigo. Cada submódulo abre con el permiso
 * de entrada de su rol; si el permiso no existe todavía se crea cuando se corra IntegrationPermissionsSeeder.
 *   php artisan db:seed --class=IntegrationNavigationSeeder   (después de IntegrationPermissionsSeeder)
 */
class IntegrationNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(['name' => 'Integraciones'], ['icon' => 'integration']);
        $module->update(['position' => ErpNavigationSeeder::MODULE_ORDER['Integraciones']]);

        $position = 0;

        // Los submódulos salen en el orden en que están definidos en IntegrationPermissionsSeeder::SUBMODULES
        foreach (IntegrationPermissionsSeeder::SUBMODULES as $slug => [$title, $entry]) {
            $position++;
            $permission = Permission::where('name', "integration.{$slug}.{$entry}")->first();

            if (!$permission) {
                continue;
            }

            Submodule::updateOrCreate(['url' => "/integration/{$slug}"], [
                'module_id' => $module->id,
                'name' => $title,
                'icon' => $slug,
                'position' => $position,
                'permission_id' => $permission->id,
            ]);
        }
    }
}

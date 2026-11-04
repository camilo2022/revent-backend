<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Submodule;
use Illuminate\Database\Seeder;

/**
 * Menú de producción del ERP. Busca cada permiso por nombre (no por número) y no repite lo que ya existe:
 *   php artisan db:seed --class=ErpNavigationSeeder   (después de ErpPermissionsSeeder)
 */
class ErpNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(['name' => 'Producción'], ['icon' => 'production']);

        $submodules = [
            ['Colecciones', '/collections', 'collections', 'collections.all'],
            ['Productos', '/products', 'products', 'products.all'],
            ['Fichas técnicas', '/technical_sheets', 'technical_sheets', 'technical_sheets.all'],
            ['Órdenes de producción', '/production_orders', 'production_orders', 'production_orders.all'],
            ['Colores', '/colors', 'colors', 'colors.all'],
            ['Tallas', '/sizes', 'sizes', 'sizes.all'],
            ['Marcas', '/trademarks', 'trademarks', 'trademarks.all'],
        ];

        foreach ($submodules as [$name, $url, $icon, $permissionName]) {
            $permission = Permission::where('name', $permissionName)->first();

            // Si el permiso no existe todavía, el submódulo se crea cuando se corra el seeder de permisos
            if (!$permission || Submodule::where('url', $url)->orWhere('permission_id', $permission->id)->exists()) {
                continue;
            }

            Submodule::create([
                'module_id' => $module->id,
                'name' => $name,
                'url' => $url,
                'icon' => $icon,
                'permission_id' => $permission->id,
            ]);
        }
    }
}

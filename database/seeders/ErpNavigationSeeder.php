<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Submodule;
use Illuminate\Database\Seeder;

/**
 * Menú del sistema (Configuración, Administración y Producción). Busca cada permiso por nombre (no por número)
 * y no repite lo que ya existe; las urls son las rutas del frontend:
 *   php artisan db:seed --class=ErpNavigationSeeder   (después de ErpPermissionsSeeder)
 */
class ErpNavigationSeeder extends Seeder
{
    public function run(): void
    {
        // Módulo => [icono, submódulos [nombre, url, icono, permiso]]
        $menu = [
            'Configuración' => ['settings', [
                ['Usuarios', '/users', 'users', 'users.all'],
                ['Personas', '/people', 'people', 'people.all'],
                ['Empleados', '/employees', 'employees', 'employees.all'],
                ['Roles', '/roles', 'roles', 'authorization.roles.all'],
                ['Permisos', '/permissions', 'permissions', 'authorization.permissions.all'],
                ['Módulos', '/modules', 'modules', 'navegation.modules.all'],
                ['Auditoría', '/audits', 'audits', 'audits.all'],
            ]],
            'Administración' => ['administration', [
                ['Áreas', '/areas', 'areas', 'organizational_structure.areas.all'],
                ['Cargos', '/positions', 'positions', 'organizational_structure.areas.positions.all'],
                ['Géneros', '/genders', 'genders', 'genders.all'],
                ['Tipos de sangre', '/blood_types', 'blood_types', 'blood_types.all'],
                ['Administradoras de riesgos', '/risk_managers', 'risk_managers', 'risk_managers.all'],
                ['Entidades de salud', '/health_entities', 'health_entities', 'health_entities.all'],
                ['Cajas de compensación', '/compensation_funds', 'compensation_funds', 'compensation_funds.all'],
                ['Fondos de pensión', '/pension_funds', 'pension_funds', 'pension_funds.all'],
                ['Tipos de archivo', '/file_types', 'file_types', 'file_types.all'],
                ['Tipos de persona', '/person_types', 'person_types', 'identification.person_types.all'],
                ['Tipos de documento', '/document_types', 'document_types', 'identification.document_types.all'],
            ]],
            'Producción' => ['production', [
                ['Colecciones', '/collections', 'collections', 'collections.all'],
                ['Productos', '/products', 'products', 'products.all'],
                ['Fichas técnicas', '/technical_sheets', 'technical_sheets', 'technical_sheets.all'],
                ['Órdenes de producción', '/production_orders', 'production_orders', 'production_orders.all'],
                ['Colores', '/colors', 'colors', 'colors.all'],
                ['Tallas', '/sizes', 'sizes', 'sizes.all'],
                ['Marcas', '/trademarks', 'trademarks', 'trademarks.all'],
                ['Categorías', '/categories', 'categories', 'classification.categories.all'],
                ['Subcategorías', '/subcategories', 'subcategories', 'classification.subcategories.all'],
                ['Proveedores', '/suppliers', 'suppliers', 'suppliers.all'],
                ['Tiendas', '/stores', 'stores', 'stores.all'],
            ]],
        ];

        foreach ($menu as $name => [$icon, $submodules]) {
            $module = Module::firstOrCreate(['name' => $name], ['icon' => $icon]);

            foreach ($submodules as [$submoduleName, $url, $submoduleIcon, $permissionName]) {
                $permission = Permission::where('name', $permissionName)->first();

                // Si el permiso no existe todavía, el submódulo se crea cuando se corra el seeder de permisos
                if (!$permission || Submodule::where('url', $url)->orWhere('permission_id', $permission->id)->exists()) {
                    continue;
                }

                Submodule::create([
                    'module_id' => $module->id,
                    'name' => $submoduleName,
                    'url' => $url,
                    'icon' => $submoduleIcon,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}

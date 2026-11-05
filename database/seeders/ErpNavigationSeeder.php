<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Submodule;
use Illuminate\Database\Seeder;

/**
 * Menú del sistema separado por módulos (Producción, Catálogos, Comercial, Administración, Parámetros y Configuración).
 * Busca cada permiso por nombre (no por número) y mueve de módulo lo que ya existe; las urls son las rutas del frontend:
 *   php artisan db:seed --class=ErpNavigationSeeder   (después de ErpPermissionsSeeder)
 */
class ErpNavigationSeeder extends Seeder
{
    public function run(): void
    {
        // Módulo => [icono, submódulos [nombre, url, icono, permiso]]
        $menu = [
            'Producción' => ['production', [
                ['Colecciones', '/collections', 'collections', 'collections.all'],
                ['Productos', '/products', 'products', 'products.all'],
                ['Fichas técnicas', '/technical_sheets', 'technical_sheets', 'technical_sheets.all'],
                ['Órdenes de producción', '/production_orders', 'production_orders', 'production_orders.all'],
            ]],
            'Reportes' => ['reports', [
                ['Reportes', '/reports', 'reports', 'reports.all'],
            ]],
            'Catálogos' => ['catalogs', [
                ['Colores', '/colors', 'colors', 'colors.all'],
                ['Tallas', '/sizes', 'sizes', 'sizes.all'],
                ['Marcas', '/trademarks', 'trademarks', 'trademarks.all'],
                ['Categorías', '/categories', 'categories', 'categorization.categories.all'],
            ]],
            'Comercial' => ['commercial', [
                ['Proveedores', '/suppliers', 'suppliers', 'suppliers.all'],
                ['Tiendas', '/stores', 'stores', 'stores.all'],
            ]],
            'Administración' => ['administration', [
                ['Personas', '/people', 'people', 'people.all'],
                ['Empleados', '/employees', 'employees', 'employees.all'],
                ['Áreas', '/areas', 'areas', 'organizational_structure.areas.all'],
                ['Cargos', '/positions', 'positions', 'organizational_structure.areas.positions.all'],
            ]],
            'Parámetros' => ['parameters', [
                ['Géneros', '/genders', 'genders', 'genders.all'],
                ['Tipos de sangre', '/blood_types', 'blood_types', 'blood_types.all'],
                ['Administradoras de riesgos', '/risk_managers', 'risk_managers', 'risk_managers.all'],
                ['Entidades de salud', '/health_entities', 'health_entities', 'health_entities.all'],
                ['Cajas de compensación', '/compensation_funds', 'compensation_funds', 'compensation_funds.all'],
                ['Fondos de pensión', '/pension_funds', 'pension_funds', 'pension_funds.all'],
                ['Tipos de archivo', '/file_types', 'file_types', 'file_types.all'],
                ['Tipos de persona', '/person_types', 'person_types', 'identification.person_types.all'],
            ]],
            'Configuración' => ['settings', [
                ['Usuarios', '/users', 'users', 'users.all'],
                ['Roles', '/roles', 'roles', 'authorization.roles.all'],
                ['Módulos', '/modules', 'modules', 'navegation.modules.all'],
            ]],
        ];

        // La auditoría ya no es un submódulo: cada registro tiene su botón de auditoría
        Submodule::where('url', '/audits')->delete();

        // Permisos se ven dentro de cada rol los tipos de documento dentro de cada tipo de persona y las subcategorías dentro de cada categoría
        Submodule::whereIn('url', ['/permissions', '/document_types', '/subcategories'])->delete();

        foreach ($menu as $name => [$icon, $submodules]) {
            $module = Module::firstOrCreate(['name' => $name], ['icon' => $icon]);

            foreach ($submodules as [$submoduleName, $url, $submoduleIcon, $permissionName]) {
                $permission = Permission::where('name', $permissionName)->first();

                // Si el permiso no existe todavía, el submódulo se crea cuando se corra el seeder de permisos
                if (!$permission) {
                    continue;
                }

                // Si la url ya existe (menú anterior) se mueve al módulo que le corresponde
                Submodule::updateOrCreate(['url' => $url], [
                    'module_id' => $module->id,
                    'name' => $submoduleName,
                    'icon' => $submoduleIcon,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}

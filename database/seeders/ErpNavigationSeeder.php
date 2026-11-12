<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Submodule;
use Illuminate\Database\Seeder;

/**
 * Menú del sistema separado por módulos y en este orden: Configuración, Administración, Parámetros, Catálogos, Comercial,
 * Producción, Integraciones y Reportes (el "Inicio" siempre va de primero). Cada módulo y cada submódulo guarda su posición.
 * Busca cada permiso por nombre (no por número) y mueve de módulo lo que ya existe; las urls son las rutas del frontend:
 *   php artisan db:seed --class=ErpNavigationSeeder   (después de ErpPermissionsSeeder)
 */
class ErpNavigationSeeder extends Seeder
{
    /** Posición de cada módulo en el menú (también la usa IntegrationNavigationSeeder). */
    public const MODULE_ORDER = [
        'Configuración' => 1,
        'Administración' => 2,
        'Parámetros' => 3,
        'Catálogos' => 4,
        'Comercial' => 5,
        'Producción' => 6,
        'Integraciones' => 7,
        'Reportes' => 8,
    ];

    public function run(): void
    {
        // Módulo => [icono, submódulos [nombre, url, icono, permiso]]; los submódulos llevan la posición en que aparecen aquí
        $menu = [
            'Configuración' => ['settings', [
                ['Usuarios', '/users', 'users', 'users.all'],
                ['Roles', '/roles', 'roles', 'authorization.roles.all'],
                ['Módulos', '/modules', 'modules', 'navegation.modules.all'],
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
            'Producción' => ['production', [
                ['Colecciones', '/collections', 'collections', 'collections.all'],
                ['Productos', '/products', 'products', 'products.all'],
                ['Fichas técnicas', '/technical_sheets', 'technical_sheets', 'technical_sheets.all'],
                ['Órdenes de producción', '/production_orders', 'production_orders', 'production_orders.all'],
            ]],
            // Sus submódulos los crea IntegrationNavigationSeeder; el módulo se crea aquí para que quede en su lugar
            'Integraciones' => ['integration', []],
            'Reportes' => ['reports', [
                ['Reportes', '/reports', 'reports', 'reports.all'],
            ]],
        ];

        // La auditoría ya no es un submódulo: cada registro tiene su botón de auditoría
        Submodule::where('url', '/audits')->delete();

        // Permisos se ven dentro de cada rol los tipos de documento dentro de cada tipo de persona y las subcategorías dentro de cada categoría
        Submodule::whereIn('url', ['/permissions', '/document_types', '/subcategories'])->delete();

        foreach ($menu as $name => [$icon, $submodules]) {
            $module = Module::firstOrCreate(['name' => $name], ['icon' => $icon]);
            $module->update(['position' => self::MODULE_ORDER[$name]]);

            foreach (array_values($submodules) as $index => [$submoduleName, $url, $submoduleIcon, $permissionName]) {
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
                    'position' => $index + 1,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}

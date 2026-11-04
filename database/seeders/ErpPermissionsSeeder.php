<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del ERP (colecciones, fichas técnicas, órdenes de producción) y el nombre correcto de los de cargos.
 * Se puede correr varias veces (o solo, en una base ya sembrada): crea lo que falta y no repite nada.
 *   php artisan db:seed --class=ErpPermissionsSeeder
 */
class ErpPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Las rutas de cargos usan `organizational_structure.areas.positions.*`, pero el seeder original los creó
        // como `organizational_structure.positions.*`. Se renombran (conservan su id, así las asignaciones siguen).
        foreach (['all', 'find', 'store', 'update', 'delete', 'restore', 'authorization.assign', 'authorization.remove'] as $action) {
            $old = Permission::where('name', "organizational_structure.positions.{$action}")->first();

            if ($old && !Permission::where('name', "organizational_structure.areas.positions.{$action}")->exists()) {
                $old->update(['name' => "organizational_structure.areas.positions.{$action}"]);
            }
        }

        $groups = [
            [
                'role' => ['name' => 'collections', 'title' => 'Colecciones', 'description' => 'Gestión de colecciones.'],
                'permissions' => [
                    ['name' => 'collections.all', 'title' => 'Listar colecciones', 'description' => 'Permite listar colecciones.'],
                    ['name' => 'collections.find', 'title' => 'Consultar colección', 'description' => 'Permite consultar una colección.'],
                    ['name' => 'collections.store', 'title' => 'Crear colección', 'description' => 'Permite crear colecciones.'],
                    ['name' => 'collections.update', 'title' => 'Actualizar colección', 'description' => 'Permite actualizar colecciones.'],
                    ['name' => 'collections.delete', 'title' => 'Eliminar colección', 'description' => 'Permite eliminar colecciones.'],
                    ['name' => 'collections.restore', 'title' => 'Restaurar colección', 'description' => 'Permite restaurar colecciones.'],
                ],
            ],
            [
                'role' => ['name' => 'reports', 'title' => 'Reportes', 'description' => 'Consulta y exportación de reportes.'],
                'permissions' => [
                    ['name' => 'reports.all', 'title' => 'Listar reportes', 'description' => 'Permite listar los reportes disponibles.'],
                    ['name' => 'reports.find', 'title' => 'Consultar reporte', 'description' => 'Permite consultar los campos y filtros de un reporte.'],
                    ['name' => 'reports.generate', 'title' => 'Generar reporte', 'description' => 'Permite generar un reporte con los campos y filtros elegidos.'],
                    ['name' => 'reports.export', 'title' => 'Exportar reporte', 'description' => 'Permite exportar un reporte a Excel.'],
                ],
            ],
            [
                'role' => ['name' => 'technical_sheets', 'title' => 'Fichas técnicas', 'description' => 'Gestión de fichas técnicas.'],
                'permissions' => [
                    ['name' => 'technical_sheets.all', 'title' => 'Listar fichas técnicas', 'description' => 'Permite listar fichas técnicas.'],
                    ['name' => 'technical_sheets.find', 'title' => 'Consultar ficha técnica', 'description' => 'Permite consultar una ficha técnica.'],
                    ['name' => 'technical_sheets.store', 'title' => 'Crear ficha técnica', 'description' => 'Permite crear fichas técnicas.'],
                    ['name' => 'technical_sheets.update', 'title' => 'Actualizar ficha técnica', 'description' => 'Permite actualizar fichas técnicas.'],
                    ['name' => 'technical_sheets.reviewed', 'title' => 'Enviar ficha técnica a revisión', 'description' => 'Permite enviar fichas técnicas a revisión.'],
                    ['name' => 'technical_sheets.approved', 'title' => 'Aprobar ficha técnica', 'description' => 'Permite aprobar fichas técnicas.'],
                    ['name' => 'technical_sheets.canceled', 'title' => 'Cancelar ficha técnica', 'description' => 'Permite cancelar fichas técnicas.'],
                ],
            ],
            [
                'role' => ['name' => 'production_orders', 'title' => 'Órdenes de producción', 'description' => 'Gestión de órdenes de producción.'],
                'permissions' => [
                    ['name' => 'production_orders.all', 'title' => 'Listar órdenes de producción', 'description' => 'Permite listar órdenes de producción.'],
                    ['name' => 'production_orders.find', 'title' => 'Consultar orden de producción', 'description' => 'Permite consultar una orden de producción.'],
                    ['name' => 'production_orders.store', 'title' => 'Crear orden de producción', 'description' => 'Permite crear órdenes de producción.'],
                    ['name' => 'production_orders.update', 'title' => 'Actualizar orden de producción', 'description' => 'Permite actualizar órdenes de producción.'],
                    ['name' => 'production_orders.approved', 'title' => 'Aprobar orden de producción', 'description' => 'Permite aprobar órdenes de producción.'],
                    ['name' => 'production_orders.canceled', 'title' => 'Cancelar orden de producción', 'description' => 'Permite cancelar órdenes de producción.'],
                ],
            ],
        ];

        $created = [];

        foreach ($groups as $group) {
            $role = Role::firstOrCreate(['name' => $group['role']['name']], [
                'title' => $group['role']['title'],
                'description' => $group['role']['description'],
            ]);

            foreach ($group['permissions'] as $item) {
                $permission = Permission::firstOrCreate(['name' => $item['name']], [
                    'title' => $item['title'],
                    'description' => $item['description'],
                ]);

                $role->givePermissionTo($permission);
                $created[] = $permission;
            }
        }

        // Quienes ya administran permisos de usuarios reciben los permisos nuevos (los demás se asignan desde Usuarios)
        User::permission('users.authorization.assign')->get()->each->givePermissionTo($created);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

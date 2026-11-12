<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos de acceso por tienda (bodega de Siigo): asignar tiendas a usuarios, acceso a todas las bodegas
 * y el listado de bodegas de Siigo para asociarlas a una tienda. Idempotente.
 *   php artisan db:seed --class=StoreAccessPermissionsSeeder
 */
class StoreAccessPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'users' => [
                ['name' => 'users.stores.all', 'title' => 'Listar tiendas del usuario', 'description' => 'Permite ver las tiendas (bodegas) asignadas a un usuario.'],
                ['name' => 'users.stores.assign', 'title' => 'Asignar tienda al usuario', 'description' => 'Permite asignar tiendas (bodegas) a un usuario.'],
                ['name' => 'users.stores.remove', 'title' => 'Quitar tienda al usuario', 'description' => 'Permite quitar tiendas (bodegas) a un usuario.'],
            ],
            'stores' => [
                ['name' => 'stores.access_all', 'title' => 'Acceso a todas las bodegas', 'description' => 'Permite operar en todas las bodegas (supervisor), sin tenerlas asignadas.'],
                ['name' => 'stores.siigo_warehouses', 'title' => 'Listar bodegas de Siigo', 'description' => 'Permite listar las bodegas de Siigo para asociarlas a una tienda.'],
            ],
        ];

        $created = [];

        foreach ($groups as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            foreach ($permissions as $item) {
                $permission = Permission::firstOrCreate(['name' => $item['name']], [
                    'title' => $item['title'],
                    'description' => $item['description'],
                ]);

                $role?->givePermissionTo($permission);
                $created[] = $permission;
            }
        }

        // Quienes ya administran permisos de usuarios reciben los permisos nuevos (los demás se asignan desde Usuarios)
        User::permission('users.authorization.assign')->get()->each->givePermissionTo($created);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

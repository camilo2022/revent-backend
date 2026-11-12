<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos de las integraciones con Siigo en la API: un rol por submódulo y un permiso por ruta
 * (`integration.{submodulo}.{accion}`). Se puede correr varias veces: crea lo que falta y no repite nada.
 *   php artisan db:seed --class=IntegrationPermissionsSeeder
 */
class IntegrationPermissionsSeeder extends Seeder
{
    /**
     * Submódulo => [título, permiso de entrada (el que abre la pantalla), acciones [acción => título]].
     * También lo lee IntegrationNavigationSeeder para armar el menú.
     */
    public const SUBMODULES = [
        'exports_inventory' => ['Exporte de inventario', 'send', ['send' => 'Enviar exporte de inventario por correo']],
        'exports_invoice' => ['Exporte de facturas', 'send', ['send' => 'Enviar exporte de facturas por correo']],
        'exports_invoice_360' => ['Exporte de facturas 360', 'send', ['send' => 'Enviar exporte de facturas 360 por correo']],
        'exports_purchase' => ['Exporte de compras', 'send', ['send' => 'Enviar exporte de compras por correo']],
        'sync_unico' => ['Sincronización con Único', 'runs', [
            'runs' => 'Ver historial de sincronizaciones',
            'run' => 'Lanzar sincronización con Único',
        ]],
        'masive_transfers' => ['Traslados masivos', 'format', [
            'format' => 'Descargar formato de traslados masivos',
            'upload' => 'Cargar traslados masivos',
        ]],
        'masive_purchase_orders' => ['Órdenes de compra masivas', 'types', [
            'types' => 'Listar tipos de orden de compra',
            'format' => 'Descargar formato de órdenes de compra masivas',
            'upload' => 'Cargar órdenes de compra masivas',
        ]],
        'product_traceability' => ['Trazabilidad de productos', 'warehouses', [
            'warehouses' => 'Listar bodegas de trazabilidad',
            'format' => 'Descargar plantilla de trazabilidad',
            'search' => 'Consultar trazabilidad de productos',
            'download' => 'Descargar trazabilidad de productos',
        ]],
        'product_photos' => ['Fotos de producto', 'search', [
            'search' => 'Buscar fotos de una referencia',
            'upload' => 'Subir fotos de una referencia',
            'delete' => 'Eliminar fotos de una referencia',
            'bulk_upload' => 'Cargar fotos de forma masiva',
            'explorer' => 'Explorar carpetas de fotos',
            'explorer_folder' => 'Crear carpetas de fotos',
            'explorer_upload' => 'Subir fotos desde el explorador y enviarlas a Siigo',
            'explorer_delete' => 'Eliminar fotos del explorador',
        ]],
        'accounts_payable' => ['Cuentas por pagar', 'settings', [
            'settings' => 'Ver configuración de cuentas por pagar',
            'providers' => 'Listar proveedores',
            'providers_sync' => 'Sincronizar proveedores con Siigo',
            'documents' => 'Consultar documentos de un proveedor',
            'payments' => 'Consultar pagos de un proveedor',
            'payments_weekly' => 'Consultar pagos semanales',
            'payment_html' => 'Ver comprobante de pago',
            'payment_store' => 'Crear pagos',
            'advance_store' => 'Crear avances',
            'conciliation_store' => 'Crear conciliaciones',
        ]],
        'inventory_filter' => ['Filtro de inventario', 'catalogs', [
            'catalogs' => 'Listar bodegas y grupos de color',
            'search' => 'Consultar inventario de una bodega',
            'all_warehouses' => 'Consultar inventario en todas las bodegas',
            'images' => 'Ver fotos de una referencia',
        ]],
        'purchase_order_reception' => ['Recepción de órdenes de compra', 'search', [
            'search' => 'Buscar orden de compra para recibir',
            'confirm' => 'Confirmar recepción de orden de compra',
        ]],
        'purchase_order_tracking' => ['Seguimiento de órdenes de compra', 'documents', [
            'documents' => 'Consultar seguimiento de órdenes de compra',
            'missing_download' => 'Descargar órdenes de compra faltantes',
        ]],
        'transfer_approval' => ['Aprobación de traslados', 'search', [
            'search' => 'Buscar nota de traslado',
            'confirm' => 'Aprobar traslado',
        ]],
        'customer_sales' => ['Clientes por venta', 'cost_centers', [
            'cost_centers' => 'Listar centros de costo',
            'search' => 'Consultar clientes por venta',
        ]],
        'search_document' => ['Buscar documento', 'types', [
            'types' => 'Listar tipos de documento',
            'transfer_note' => 'Buscar nota de traslado',
            'purchase_order' => 'Buscar orden de compra',
            'transfer_note_pdf' => 'Descargar PDF de nota de traslado',
            'purchase_order_pdf' => 'Descargar PDF de orden de compra',
        ]],
    ];

    public function run(): void
    {
        $created = [];

        foreach (self::SUBMODULES as $slug => [$title, $entry, $actions]) {
            $role = Role::firstOrCreate(['name' => "integration.{$slug}"], [
                'title' => "Integraciones: {$title}",
                'description' => "Acceso al submódulo {$title} de Integraciones.",
            ]);

            foreach ($actions as $action => $actionTitle) {
                // Los títulos de permisos son únicos: se antepone el submódulo ("Cuentas por pagar: Listar proveedores")
                $permission = Permission::firstOrCreate(['name' => "integration.{$slug}.{$action}"], [
                    'title' => "{$title}: {$actionTitle}",
                    'description' => "Permite: {$actionTitle} (Integraciones, {$title}).",
                ]);

                $role->givePermissionTo($permission);
                $created[] = $permission;
            }
        }

        // Probar el token y la cookie de Siigo (pantalla Autorización Siigo)
        $role = Role::firstOrCreate(['name' => 'integration.credentials'], [
            'title' => 'Integraciones: Autorización Siigo',
            'description' => 'Permite probar el token y la cookie de Siigo.',
        ]);

        $permission = Permission::firstOrCreate(['name' => 'integration.credentials.check'], [
            'title' => 'Probar credenciales de Siigo',
            'description' => 'Permite: Probar credenciales de Siigo.',
        ]);

        $role->givePermissionTo($permission);
        $created[] = $permission;

        // Quienes ya administran permisos de usuarios reciben los permisos nuevos (los demás se asignan desde Usuarios)
        User::permission('users.authorization.assign')->get()->each->givePermissionTo($created);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

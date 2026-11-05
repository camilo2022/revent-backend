<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetailQuantity;
use App\Models\Report;
use App\Models\TechnicalSheet;
use Illuminate\Database\Seeder;

/**
 * Reportes disponibles (subítems del ítem Reportes). Cada uno define el modelo, los campos que se pueden elegir
 * (agrupados) y los filtros. Se puede correr varias veces: actualiza la definición de cada reporte por su nombre.
 *   php artisan db:seed --class=ErpReportsSeeder
 */
class ErpReportsSeeder extends Seeder
{
    private const STATUS_SHEET = ['Pendiente', 'En revision', 'Aprobado', 'Cancelado'];

    private const STATUS_ORDER = ['Pendiente', 'Aprobado', 'Cancelado'];

    public function run(): void
    {
        // El id es fijo porque el modelo Report lo usa como constante (ITEM_ID)
        if (!Item::where('id', Report::ITEM_ID)->exists()) {
            (new Item())->forceFill([
                'id' => Report::ITEM_ID,
                'name' => 'Reportes',
                'description' => 'Reportes del sistema con campos y filtros configurables.',
            ])->save();
        }

        foreach ($this->reports() as $report) {
            $existing = Report::withTrashed()->where('name', $report['name'])->first();

            $existing
                ? $existing->update(['description' => $report['description'], 'settings' => $report['settings']])
                : Report::create($report);
        }
    }

    private function field(string $label, string $key, string $type = 'text'): array
    {
        return ['label' => $label, 'data_key' => $key, 'type' => $type];
    }

    private function select(string $label, string $data, string $key, ?array $options = null, bool $default = false): array
    {
        return ['label' => $label, 'data' => $data, 'data_key' => $key, 'type' => 'select'] + ($options ? ['options' => $options] : []) + ($default ? ['show_by_default' => true] : []);
    }

    private function range(string $label, string $key, bool $default = false): array
    {
        return ['label' => $label, 'data' => $key, 'data_key' => $key, 'type' => 'date_range'] + ($default ? ['show_by_default' => true] : []);
    }

    private function statuses(array $list): array
    {
        return array_map(fn ($s) => ['value' => $s, 'label' => $s === 'En revision' ? 'En revisión' : $s], $list);
    }

    private function reports(): array
    {
        return [
            [
                'name' => 'Productos',
                'description' => 'Referencias con su colección, precios y datos de la ficha técnica.',
                'settings' => [
                    'model' => Product::class,
                    'fields' => [
                        ['name' => 'Producto', 'fields' => [
                            $this->field('Código', 'code'), $this->field('Nombre', 'name'), $this->field('Colección', 'collection.name'),
                            $this->field('Costo', 'cost', 'money'), $this->field('Precio', 'price', 'money'), $this->field('Creado', 'created_at', 'datetime'),
                        ]],
                        ['name' => 'Ficha técnica', 'fields' => [
                            $this->field('Estado de la ficha', 'technical_sheet.status'), $this->field('Marca', 'technical_sheet.trademark.name'),
                            $this->field('Categoría', 'technical_sheet.category.name'), $this->field('Subcategoría', 'technical_sheet.subcategory.name'),
                        ]],
                    ],
                    'filters' => [
                        'collection' => $this->select('Colección', 'collections', 'collection_id', null, true),
                        'sheet_status' => $this->select('Estado de la ficha', 'status', 'technical_sheet.status', $this->statuses(self::STATUS_SHEET)),
                        'category' => $this->select('Categoría', 'categories', 'technical_sheet.category_id'),
                        'created' => $this->range('Fecha de creación', 'created_at'),
                    ],
                ],
            ],
            [
                'name' => 'Fichas técnicas',
                'description' => 'Fichas técnicas con su producto, clasificación y aprobación.',
                'settings' => [
                    'model' => TechnicalSheet::class,
                    'fields' => [
                        ['name' => 'Producto', 'fields' => [$this->field('Código', 'product.code'), $this->field('Nombre', 'product.name'), $this->field('Colección', 'product.collection.name')]],
                        ['name' => 'Ficha técnica', 'fields' => [
                            $this->field('Estado', 'status'), $this->field('Marca', 'trademark.name'), $this->field('Categoría', 'category.name'),
                            $this->field('Subcategoría', 'subcategory.name'), $this->field('Proceso', 'process'), $this->field('Observación', 'observation'),
                            $this->field('Creada', 'created_at', 'datetime'),
                        ]],
                        ['name' => 'Aprobación', 'fields' => [$this->field('Aprobada por', 'approver.username'), $this->field('Fecha de aprobación', 'approved_at', 'datetime')]],
                    ],
                    'filters' => [
                        'status' => $this->select('Estado', 'status', 'status', $this->statuses(self::STATUS_SHEET), true),
                        'trademark' => $this->select('Marca', 'trademarks', 'trademark_id'),
                        'category' => $this->select('Categoría', 'categories', 'category_id'),
                        'subcategory' => $this->select('Subcategoría', 'subcategories', 'subcategory_id'),
                        'created' => $this->range('Fecha de creación', 'created_at'),
                    ],
                ],
            ],
            [
                'name' => 'Órdenes de producción',
                'description' => 'Órdenes con su proveedor, producto, estado y fecha de entrega.',
                'settings' => [
                    'model' => ProductionOrder::class,
                    'fields' => [
                        ['name' => 'Orden', 'fields' => [
                            $this->field('Consecutivo', 'consecutive'), $this->field('Estado', 'status'), $this->field('Fecha de entrega', 'due_date', 'date'),
                            $this->field('% IVA', 'vat_percentage'), $this->field('% Remisión', 'delivery_note_percentage'), $this->field('Creada', 'created_at', 'datetime'),
                        ]],
                        ['name' => 'Proveedor', 'fields' => [$this->field('Razón social', 'supplier.legal_name'), $this->field('Nombre comercial', 'supplier.trade_name')]],
                        ['name' => 'Producto', 'fields' => [$this->field('Código', 'technical_sheet.product.code'), $this->field('Nombre', 'technical_sheet.product.name')]],
                    ],
                    'filters' => [
                        'status' => $this->select('Estado', 'status', 'status', $this->statuses(self::STATUS_ORDER), true),
                        'supplier' => $this->select('Proveedor', 'suppliers', 'supplier_id', null, true),
                        'due_date' => $this->range('Fecha de entrega', 'due_date'),
                    ],
                ],
            ],
            [
                'name' => 'Cantidades por orden',
                'description' => 'Pares de cada orden por color, talla y tienda.',
                'settings' => [
                    'model' => ProductionOrderDetailQuantity::class,
                    'fields' => [
                        ['name' => 'Orden', 'fields' => [
                            $this->field('Consecutivo', 'production_order_detail.production_order.consecutive'),
                            $this->field('Estado', 'production_order_detail.production_order.status'),
                            $this->field('Fecha de entrega', 'production_order_detail.production_order.due_date', 'date'),
                        ]],
                        ['name' => 'Detalle', 'fields' => [
                            $this->field('Color', 'production_order_detail.product_detail.assignable.name'),
                            $this->field('Talla', 'product_detail.assignable.name'),
                            $this->field('Tienda', 'store.name'),
                            $this->field('Cantidad (pares)', 'quantity'),
                        ]],
                    ],
                    // Tallas en horizontal: una columna por talla con la cantidad como valor (la curva)
                    'pivot' => ['label' => 'Tallas en horizontal (curva)', 'key' => 'product_detail.assignable.name', 'value' => 'quantity', 'total_label' => 'Total pares'],
                    'filters' => [
                        'status' => $this->select('Estado de la orden', 'status', 'production_order_detail.production_order.status', $this->statuses(self::STATUS_ORDER), true),
                        'store' => $this->select('Tienda', 'stores', 'store_id'),
                        'supplier' => $this->select('Proveedor', 'suppliers', 'production_order_detail.production_order.supplier_id', null, true),
                        'size' => $this->select('Talla', 'sizes', 'product_detail.assignable_id'),
                        'color' => $this->select('Color', 'colors', 'production_order_detail.product_detail.assignable_id'),
                        'due_date' => $this->range('Fecha de entrega', 'production_order_detail.production_order.due_date'),
                    ],
                ],
            ],
        ];
    }
}

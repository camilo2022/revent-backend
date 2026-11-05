<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\BloodType;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\CompensationFund;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\FileType;
use App\Models\Gender;
use App\Models\HealthEntity;
use App\Models\PensionFund;
use App\Models\Person;
use App\Models\Position;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantity;
use App\Models\RiskManager;
use App\Models\Size;
use App\Models\Store;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\TechnicalSheet;
use App\Models\Trademark;
use App\Models\User;
use App\Services\ProductCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de prueba "casi reales" para revisar el sistema. NO va en DatabaseSeeder (no debe correr en producción):
 *   php artisan db:seed --class=DemoDataSeeder
 * Colores, tallas, tipos de calzado, referencias, precios y dos proveedores salen de una consulta de solo lectura a Siigo;
 * tiendas, áreas, cargos, personas y teléfonos/correos son de ejemplo. Se puede correr varias veces sin duplicar.
 */
class DemoDataSeeder extends Seeder
{
    // Colores de Siigo: código de 2 dígitos => nombre
    private const COLORS = [
            '44' => 'MIEL',
            '99' => 'NEGRO',
            '17' => 'BEIGE',
            '79' => 'CAFE',
            '10' => 'BLANCO',
            '21' => 'CHAMPAÑA',
            '90' => 'VINO',
            '53' => 'AMARETO',
            '45' => 'AREQUIPE',
            '19' => 'PERLA',
            '73' => 'TAUPE',
            '42' => 'DORADO',
            '36' => 'PLATA',
            '39' => 'OROROSA',
            '18' => 'CREMA',
            '85' => 'MOKA',
            '33' => 'ARENA',
            '96' => 'OSCURO',
            '24' => 'CRUDO',
            '47' => 'CAMEL',
            '27' => 'VAINILLA',
            '30' => 'NUDE',
            '95' => 'PLOMO',
        ];

    // Tipos de calzado de Siigo: código de 2 letras => nombre
    private const TYPES = [
            'BN' => 'BOTIN',
            'BB' => 'BABUCHA',
            'BT' => 'BOTAS',
            'PL' => 'PLANA',
            'CH' => 'CHUNKY',
            'TC' => 'TACON',
            'BA' => 'BALETA',
            'TE' => 'TENIS',
            'SE' => 'SENA',
            'KT' => 'KITTEN HEELS',
            'ST' => 'STILETTO',
            'PT' => 'PLATAFORMA',
            'CA' => 'CANOA',
            'MO' => 'MOCASIN',
        ];

    // Referencias reales de Siigo (nombre, tipo, precio, colores y tallas)
    private const REFS = [[
            'name' => 'AMBER',
            'tt' => 'BA',
            'price' => 74990,
            'colors' => [
                '21' => 'CHAMPAÑA',
                '10' => 'BLANCO',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'AVERY',
            'tt' => 'BA',
            'price' => 74990,
            'colors' => [
                '10' => 'BLANCO',
                '99' => 'NEGRO',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'RUMANIA',
            'tt' => 'BB',
            'price' => 99990,
            'colors' => [
                '99' => 'NEGRO',
                '17' => 'BEIGE',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'RUSTY',
            'tt' => 'BB',
            'price' => 159990,
            'colors' => [
                '99' => 'NEGRO',
                '33' => 'ARENA',
                '17' => 'BEIGE',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'ANGUS',
            'tt' => 'BN',
            'price' => 184990,
            'colors' => [
                '99' => 'NEGRO',
                '17' => 'BEIGE',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'ARIZONA',
            'tt' => 'BN',
            'price' => 139990,
            'colors' => [
                '79' => 'CAFE',
                '73' => 'TAUPE',
                '99' => 'NEGRO',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'CANDADO',
            'tt' => 'BT',
            'price' => 179990,
            'colors' => [
                '99' => 'NEGRO',
                '79' => 'CAFE',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'SILVINA',
            'tt' => 'CA',
            'price' => 99990,
            'colors' => [
                '53' => 'AMARETO',
                '99' => 'NEGRO',
                '10' => 'BLANCO',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'ALMAGRO',
            'tt' => 'CH',
            'price' => 119990,
            'colors' => [
                '10' => 'BLANCO',
                '99' => 'NEGRO',
                '79' => 'CAFE',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'BOYS',
            'tt' => 'CH',
            'price' => 119990,
            'colors' => [
                '73' => 'TAUPE',
                '99' => 'NEGRO',
                '79' => 'CAFE',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'YUYA',
            'tt' => 'KT',
            'price' => 129990,
            'colors' => [
                '90' => 'VINO',
                '99' => 'NEGRO',
                '33' => 'ARENA',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'LAMINE',
            'tt' => 'MO',
            'price' => 189990,
            'colors' => [
                '17' => 'BEIGE',
                '79' => 'CAFE',
                '44' => 'MIEL',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'MOR',
            'tt' => 'MO',
            'price' => 189990,
            'colors' => [
                '17' => 'BEIGE',
                '99' => 'NEGRO',
                '44' => 'MIEL',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ], [
            'name' => 'ALMEJAL',
            'tt' => 'PL',
            'price' => 64990,
            'colors' => [
                '10' => 'BLANCO',
                '79' => 'CAFÉ',
                '99' => 'NEGRO',
            ],
            'sizes' => ['35', '36', '37', '38', '39', '40'],
        ]];

    // Proveedores reales de calzado en Siigo
    private const SUPPLIERS = [[
            'name' => 'SASOLO S.A.S.',
            'document' => '901937366',
        ], [
            'name' => 'LORD HERNANDEZ S.A.S.',
            'document' => '901723768',
        ]];

    // Tiendas de Revent (las mismas que usa el proyecto en los exportes de Siigo)
    private const STORES = [
        'G2' => 'ALEGRA', 'P7' => 'PUNTO DE VENTA', 'M3' => 'MAYALES', 'R1' => 'REVENT', 'P2' => 'OCEAN MALL', 'P1' => 'NUESTRO',
        'P3' => 'ALAMEDAS', 'M2' => 'PORTAL', 'M8' => 'PLAZA DEL SOL', 'G1' => 'WEB', 'G4' => 'CASTELLANA', 'G3' => 'UNICO BQ',
        'P4' => 'CARNAVAL', 'M7' => 'GUATAPURI', 'M6' => 'VENTURA PLAZA', 'M5' => 'FABRICATO', 'M1' => 'UNICO CALI',
        'G5' => 'CARIBE PLAZA 2', 'M4' => 'MAYORCA', 'I3' => 'GRAN MANZANA', 'P6' => 'NUESTRO ATLANTICO',
    ];

    private const AREAS = [
        'DISEÑO' => ['DISEÑADOR DE CALZADO', 'PATRONISTA'],
        'PRODUCCION' => ['JEFE DE PRODUCCION', 'SUPERVISOR DE PLANTA'],
        'PLANEACION' => ['PLANEADOR DE PRODUCCION'],
        'COMPRAS' => ['ANALISTA DE COMPRAS'],
        'COMERCIAL' => ['GERENTE DE TIENDA', 'VENDEDOR'],
        'CONTABILIDAD' => ['CONTADOR', 'AUXILIAR CONTABLE'],
        'BODEGA' => ['AUXILIAR DE BODEGA'],
        'TECNOLOGIA' => ['DESARROLLADOR'],
    ];

    private const PEOPLE = [
        ['Laura Camila', 'Restrepo Gil', 'DISEÑADOR DE CALZADO'], ['Andrés Felipe', 'Cardona Ruiz', 'PATRONISTA'],
        ['Natalia', 'Ospina Vélez', 'PLANEADOR DE PRODUCCION'], ['Juan David', 'Mejía Torres', 'ANALISTA DE COMPRAS'],
        ['Valentina', 'Zapata Arias', 'CONTADOR'], ['Sebastián', 'Londoño Pérez', 'JEFE DE PRODUCCION'],
    ];

    public function run(): void
    {
        $this->command?->info('Datos de prueba: catálogos…');
        $this->catalogs();
        $this->categorization();
        $country = Country::where('name', 'like', 'Colombia%')->first() ?? Country::first();
        $this->stores($country);
        $this->suppliers($country);
        $this->people($country);

        $this->command?->info('Datos de prueba: productos, fichas y órdenes…');
        $this->products();
        $this->orders();

        $this->command?->info(sprintf(
            'Listo: %d colores, %d tallas, %d productos, %d fichas, %d órdenes, %d tiendas, %d proveedores, %d empleados.',
            Color::count(), Size::count(), Product::count(), TechnicalSheet::count(), ProductionOrder::count(), Store::count(), Supplier::count(), Employee::count()
        ));
    }

    // Crea el registro de un catálogo (ítem/subítem) si no existe por su nombre
    private function subitem(string $class, string $name, ?string $description = null, array $settings = []): Model
    {
        return $class::firstOrCreate(['name' => $name], ['description' => $description ?? $name, 'settings' => (object) $settings]);
    }

    private function catalogs(): void
    {
        foreach (['FEMENINO', 'MASCULINO'] as $name) $this->subitem(Gender::class, $name);
        foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $name) $this->subitem(BloodType::class, $name, "Tipo de sangre $name");
        foreach (['SURA EPS', 'SANITAS EPS', 'NUEVA EPS', 'COMPENSAR EPS', 'SALUD TOTAL EPS', 'COOSALUD EPS'] as $name) $this->subitem(HealthEntity::class, $name);
        foreach (['PORVENIR', 'PROTECCION', 'COLFONDOS', 'COLPENSIONES'] as $name) $this->subitem(PensionFund::class, $name);
        foreach (['COMFAMA', 'COMFENALCO ANTIOQUIA', 'CAFAM', 'COMFANDI'] as $name) $this->subitem(CompensationFund::class, $name);
        foreach (['ARL SURA', 'POSITIVA', 'COLMENA SEGUROS', 'AXA COLPATRIA'] as $name) $this->subitem(RiskManager::class, $name);
        foreach (['Ficha técnica (PDF)', 'Foto de producto', 'Catálogo'] as $name) $this->subitem(FileType::class, $name);

        foreach (self::AREAS as $area => $positions) {
            $area = $this->subitem(Area::class, $area);

            foreach ($positions as $position) {
                $this->subitem(Position::class, $position)->area()->syncWithoutDetaching([$area->id]);
            }
        }

        foreach (self::COLORS as $code => $name) $this->subitem(Color::class, mb_strtoupper($name), $name, ['code' => (string) $code]);
        foreach (range(35, 41) as $number) $this->subitem(Size::class, "T$number", "Talla $number", ['code' => (string) $number]);
    }

    // Categorías y tipos de calzado (subcategorías) con su relación
    private function categorization(): void
    {
        $category = $this->subitem(Category::class, 'CALZADO', 'Calzado', ['code' => 'CZ']);
        $this->subitem(Category::class, 'ACCESORIOS', 'Accesorios', ['code' => 'AC']);

        foreach (self::TYPES as $code => $name) {
            $this->subitem(Subcategory::class, $name, ucfirst(mb_strtolower($name)), ['code' => $code])->category()->sync([$category->id]);
        }

        $this->subitem(Collection::class, 'COLECCION 4 2026', 'Colección de mitad de año 2026', ['year' => '26', 'code' => '4', 'last_number' => 0]);
        $this->subitem(Collection::class, 'COLECCION 5 2026', 'Colección de fin de año 2026', ['year' => '26', 'code' => '5', 'last_number' => 0]);
    }

    private function stores(Country $country): void
    {
        foreach (self::STORES as $code => $name) {
            Store::firstOrCreate(['code' => $code], [
                'name' => $name, 'location_id' => $country->id, 'location_type' => Country::class, 'address' => 'Por definir', 'neighborhood' => 'Por definir',
            ]);
        }
    }

    private function suppliers(Country $country): void
    {
        $nit = DocumentType::where('settings->code', 'NIT')->first() ?? DocumentType::first();

        // Dos reales de Siigo y tres de ejemplo (teléfonos y correos inventados)
        $list = array_map(fn ($s) => ['name' => $s['name'], 'document' => $s['document']], self::SUPPLIERS);
        $list[] = ['name' => 'CALZADOS DEL NORTE S.A.S.', 'document' => '900100001'];
        $list[] = ['name' => 'MANUFACTURAS LA FORTUNA S.A.S.', 'document' => '900100002'];
        $list[] = ['name' => 'TALLER SAN JOSE S.A.S.', 'document' => '900100003'];

        foreach ($list as $i => $supplier) {
            Supplier::firstOrCreate(['document' => $supplier['document']], [
                'code' => chr(65 + intdiv($i, 3)) . chr(65 + $i % 3 + 10), 'legal_name' => $supplier['name'], 'trade_name' => ucwords(mb_strtolower(explode(' S.A.S', $supplier['name'])[0])),
                'document_type_id' => $nit->id, 'location_id' => $country->id, 'location_type' => Country::class,
                'address' => 'Por definir', 'neighborhood' => 'Por definir', 'phone_country_id' => $country->id,
                'phone' => '30000010' . sprintf('%02d', $i + 1), 'email' => 'proveedor' . ($i + 1) . '@demo.revent.test',
            ]);
        }
    }

    private function people(Country $country): void
    {
        $cc = DocumentType::where('settings->code', 'CC')->first() ?? DocumentType::first();
        $risk = RiskManager::where('name', 'ARL SURA')->first();
        $health = HealthEntity::where('name', 'SURA EPS')->first();
        $pension = PensionFund::where('name', 'PORVENIR')->first();
        $compensation = CompensationFund::where('name', 'COMFAMA')->first();
        $genders = Gender::whereIn('name', ['FEMENINO', 'MASCULINO'])->pluck('id', 'name');
        $blood = BloodType::where('name', 'O+')->first();

        foreach (self::PEOPLE as $i => [$names, $last, $position]) {
            $person = Person::firstOrCreate(['document' => (string) (1000200300 + $i)], [
                'names' => $names, 'last_names' => $last, 'document_type_id' => $cc->id, 'gender_id' => $genders[$i % 2 === 0 ? 'FEMENINO' : 'MASCULINO'],
                'birth_date' => (1985 + $i) . '-0' . ($i + 1) . '-1' . $i, 'blood_type_id' => $blood->id, 'location_id' => $country->id, 'location_type' => Country::class,
                'address' => 'Por definir', 'neighborhood' => 'Por definir', 'phone_country_id' => $country->id,
                'phone' => '31000020' . sprintf('%02d', $i + 1), 'email' => 'empleado' . ($i + 1) . '@demo.revent.test',
            ]);

            Employee::firstOrCreate(['person_id' => $person->id], [
                'position_id' => Position::where('name', $position)->value('id'), 'risk_manager_id' => $risk->id, 'health_entity_id' => $health->id,
                'pension_fund_id' => $pension->id, 'compensation_fund_id' => $compensation->id, 'start_date' => '2025-01-15',
            ]);
        }
    }

    private function products(): void
    {
        $collection = Collection::where('name', 'COLECCION 5 2026')->first();
        $trademark = Trademark::where('name', 'REVENT')->first();
        $category = Category::where('name', 'CALZADO')->first();
        $approver = User::first();

        foreach (self::REFS as $index => $ref) {
            if (Product::where('name', $ref['name'])->where('collection_id', $collection->id)->exists()) continue;

            DB::transaction(function () use ($ref, $index, $collection, $trademark, $category, $approver) {
                $type = self::TYPES[$ref['tt']];

                $product = Product::create([
                    'code' => app(ProductCodeService::class)->next($collection->id), 'name' => $ref['name'], 'collection_id' => $collection->id,
                    'description' => "$type {$ref['name']}", 'cost' => round($ref['price'] * 0.42, -2), 'price' => $ref['price'],
                ]);

                foreach ($ref['colors'] as $code => $colorName) {
                    $color = $this->subitem(Color::class, mb_strtoupper($colorName), $colorName, ['code' => (string) $code]);
                    $detail = ProductDetail::create(['model_type' => Product::class, 'model_id' => $product->id, 'assignable_type' => Color::class, 'assignable_id' => $color->id]);

                    foreach ($ref['sizes'] as $number) {
                        ProductDetail::create([
                            'model_type' => ProductDetail::class, 'model_id' => $detail->id, 'assignable_type' => Size::class,
                            'assignable_id' => Size::where('name', "T$number")->value('id'),
                        ]);
                    }
                }

                // Las primeras fichas aprobadas, algunas en revisión y otras pendientes
                $status = $index < 8 ? TechnicalSheet::APPROVED : ($index < 10 ? TechnicalSheet::REVIEW : TechnicalSheet::PENDING);

                TechnicalSheet::create([
                    'product_id' => $product->id, 'trademark_id' => $trademark->id, 'category_id' => $category->id,
                    'subcategory_id' => Subcategory::where('name', $type)->value('id'),
                    'process' => in_array($ref['tt'], ['TE', 'CH', 'PL']) ? 'Corte, guarnecida, inyección de suela, acabado y empaque.' : 'Corte, guarnecida, montaje, acabado y empaque.',
                    'description' => "$type {$ref['name']} de la colección fin de año 2026.",
                    'specifications' => ['horma' => 'H-' . (40 + $index), 'suela' => $ref['tt'] === 'TC' ? 'Tacón de poliuretano' : 'Caucho antideslizante', 'material' => 'Sintético premium', 'forro' => 'Textil suave'],
                    'status' => $status, 'approved_by' => $status === TechnicalSheet::APPROVED ? $approver->id : null,
                    'approved_at' => $status === TechnicalSheet::APPROVED ? now() : null,
                ]);
            });
        }
    }

    // Tres órdenes con varias tiendas, colores y tallas (una aprobada y dos pendientes)
    private function orders(): void
    {
        if (ProductionOrder::count() > 0) return;

        $sheets = TechnicalSheet::where('status', TechnicalSheet::APPROVED)->with('product')->orderBy('id')->take(3)->get();
        $suppliers = Supplier::orderBy('id')->get();
        $stores = Store::orderBy('code')->get();
        $curve = [1, 2, 3, 3, 2, 1];

        foreach ($sheets as $i => $sheet) {
            DB::transaction(function () use ($sheet, $i, $suppliers, $stores, $curve) {
                $order = ProductionOrder::create([
                    'consecutive' => DB::selectOne('CALL production_order_consecutive()')->consecutive,
                    'technical_sheet_id' => $sheet->id, 'supplier_id' => $suppliers[$i % $suppliers->count()]->id,
                    'due_date' => now()->addDays(30 + $i * 7)->format('Y-m-d'), 'vat_percentage' => 100, 'delivery_note_percentage' => 0,
                    'status' => $i === 0 ? ProductionOrder::APPROVED : ProductionOrder::PENDING,
                ]);

                $colors = ProductDetail::where('model_type', Product::class)->where('model_id', $sheet->product_id)->with('sizes')->orderBy('id')->get();

                foreach ($colors as $c => $color) {
                    $detail = ProductionOrderDetail::create([
                        'production_order_id' => $order->id, 'product_detail_id' => $color->id, 'cost' => $sheet->product->cost, 'price' => $sheet->product->price,
                        'observation' => $c === 0 ? 'Color principal de la referencia.' : null,
                    ]);

                    $sizes = $color->sizes->sortBy(fn ($size) => $size->assignable?->name, SORT_NATURAL)->values();

                    foreach ($stores->take(8 + 2 * $i) as $s => $store) {
                        // No todas las tiendas llevan todos los colores
                        if (($s * 3 + $c) % 7 === 0) continue;

                        foreach ($sizes as $k => $size) {
                            ProductionOrderDetailQuantity::create([
                                'production_order_detail_id' => $detail->id, 'store_id' => $store->id, 'product_detail_id' => $size->id,
                                'quantity' => ($curve[$k] ?? 1) * (1 + ($s + $c) % 3),
                            ]);
                        }
                    }
                }
            });
        }
    }
}

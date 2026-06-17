<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\BackType;
use App\Models\RiskManager;
use App\Models\BloodType;
use App\Models\BootType;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\CompensationFund;
use App\Models\FabricType;
use App\Models\HealthEntity;
use App\Models\FileSubtype;
use App\Models\FileType;
use App\Models\GarmentType;
use App\Models\Gender;
use App\Models\Group;
use App\Models\Item;
use App\Models\Operation;
use App\Models\PensionFund;
use App\Models\Piece;
use App\Models\Position;
use App\Models\Process;
use App\Models\Silhouette;
use App\Models\Size;
use App\Models\Subcategory;
use App\Models\Subgroup;
use App\Models\Subprocess;
use App\Models\Supplier;
use App\Models\SupplyType;
use App\Models\ThreadType;
use App\Models\Trademark;
use App\Models\Variant;
use App\Models\WaistbandType;
use App\Models\WashTone;
use App\Models\YokeType;
use Illuminate\Database\Seeder;

class ItemsAndSubitemsSeeder extends Seeder
{
    public function run(): void
    {

        $item = new Item();
        $item->name = 'Tipos de archivos';
        $item->description = 'Clasificación estandarizada de archivos según su naturaleza, uso y propósito dentro de procesos administrativos, legales o documentales.';
        $item->save();

        FileType::insert([
            ['item_id' => FileType::ITEM_ID, 'name' => 'Archivos personales', 'description' => 'Documentos relacionados con la información personal del empleado.'],
            ['item_id' => FileType::ITEM_ID, 'name' => 'Archivos multimedia', 'description' => 'Archivos en formato imagen, video, audio u otros formatos digitales.'],
            ['item_id' => FileType::ITEM_ID, 'name' => 'Archivos de productos', 'description' => 'Archivos en formato imagen o video relacionados con productos.'],
        ]);

        $item = new Item();
        $item->name = 'Subtipos de archivos';
        $item->description = 'Clasificación específica de archivos según su contenido y función.';
        $item->save();

        FileSubtype::insert([
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Documento de identidad', 'description' => 'Cédula, tarjeta de identidad o documento oficial.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Hoja de vida', 'description' => 'Currículum vitae del empleado.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Certificados laborales', 'description' => 'Soportes de experiencia laboral previa.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Certificados académicos', 'description' => 'Diplomas, actas o certificados de estudio.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'RUT', 'description' => 'Registro Único Tributario.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Foto', 'description' => 'Fotografía.'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Imagen', 'description' => 'Archivo de imagen (JPG, PNG, etc.).'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Video', 'description' => 'Archivo de video (MP4, AVI, etc.).'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Audio', 'description' => 'Archivo de audio (MP3, WAV, etc.).'],
            ['item_id' => FileSubtype::ITEM_ID, 'name' => 'Documento digital', 'description' => 'Archivo digital como PDF, Word o Excel.']
        ]);

        $item = new Item();
        $item->name = 'Áreas';
        $item->description = 'Listado de áreas disponibles en la organización.';
        $item->save();

        $area = new Area();
        $area->name = 'No aplica';
        $area->description = 'No aplica';
        $area->save();

        $item = new Item();
        $item->name = 'Cargos';
        $item->description = 'Listado de cargos disponibles en la organización.';
        $item->save();

        $position = new Position();
        $position->name = 'No aplica';
        $position->description = 'No aplica';
        $position->save();

        $position->area()->sync([$area->id]);

        $item = new Item();
        $item->name = 'Géneros';
        $item->description = 'Listado de géneros disponibles.';
        $item->save();

        $gender = new Gender();
        $gender->name = 'No aplica';
        $gender->description = 'No aplica';
        $gender->save();

        $item = new Item();
        $item->name = 'Tipos de sangre';
        $item->description = 'Listado de tipos de sangre.';
        $item->save();

        $blood_type = new BloodType();
        $blood_type->name = 'No aplica';
        $blood_type->description = 'No aplica';
        $blood_type->save();

        $item = new Item();
        $item->name = 'Administradora de Riesgos';
        $item->description = 'Listado de administradoras de riesgos laborales.';
        $item->save();

        $risk_manager = new RiskManager();
        $risk_manager->name = 'No aplica';
        $risk_manager->description = 'No aplica';
        $risk_manager->save();

        $item = new Item();
        $item->name = 'Entidades de Salud';
        $item->description = 'Listado de entidades promotoras de salud.';
        $item->save();

        $health_entity = new HealthEntity();
        $health_entity->name = 'No aplica';
        $health_entity->description = 'No aplica';
        $health_entity->save();

        $item = new Item();
        $item->name = 'Fondos de pensión';
        $item->description = 'Listado de fondos de pensión.';
        $item->save();

        $pension_fund = new PensionFund();
        $pension_fund->name = 'No aplica';
        $pension_fund->description = 'No aplica';
        $pension_fund->save();

        $item = new Item();
        $item->name = 'Cajas de compensación';
        $item->description = 'Listado de cajas de compensación.';
        $item->save();

        $compensation_fund = new CompensationFund();
        $compensation_fund->name = 'No aplica';
        $compensation_fund->description = 'No aplica';
        $compensation_fund->save();

        $item = new Item();
        $item->name = 'Categorías';
        $item->description = 'Listado de categorías.';
        $item->save();

        $categories = [];

        $categories['NO APLICA'] = Category::create(['name' => 'NO APLICA', 'description' => 'NO APLICA']);
        $categories['CLASICO'] = Category::create(['name' => 'CLASICO', 'description' => 'CLASICO']);
        $categories['MOLDEADOR'] = Category::create(['name' => 'MOLDEADOR', 'description' => 'MOLDEADOR']);
        $categories['MINILOOK'] = Category::create(['name' => 'MINILOOK', 'description' => 'MINILOOK']);
        $categories['MODA'] = Category::create(['name' => 'MODA', 'description' => 'MODA']);
        $categories['MONOLOOK'] = Category::create(['name' => 'MONOLOOK', 'description' => 'MONOLOOK']);

        $item = new Item();
        $item->name = 'Subcategorías';
        $item->description = 'Listado de subcategorías.';
        $item->save();

        $subcategories = [
            ['name' => 'NO APLICA', 'category' => 'NO APLICA',],
            ['name' => 'JEAN SKINNY', 'category' => 'CLASICO',],
            ['name' => 'JEAN SKINNY CARGO', 'category' => 'CLASICO',],
            ['name' => 'CLASICO CABALLERO', 'category' => 'CLASICO'],
            ['name' => 'JEAN MOLDEADOR SIN BOLSILLO', 'category' => 'MOLDEADOR'],
            ['name' => 'JEAN MOLDEADOR CON BOLSILLO', 'category' => 'MOLDEADOR'],
            ['name' => 'FALDA LARGA', 'category' => 'MINILOOK'],
            ['name' => 'FALDA MIDI', 'category' => 'MINILOOK'],
            ['name' => 'FALDA CORTA', 'category' => 'MINILOOK'],
            ['name' => 'FALDA SHORT', 'category' => 'MINILOOK'],
            ['name' => 'SHORT LARGO', 'category' => 'MINILOOK'],
            ['name' => 'SHORT CORTO', 'category' => 'MINILOOK'],
            ['name' => 'SHORT MINI', 'category' => 'MINILOOK'],
            ['name' => 'BERMUDA CLASICA', 'category' => 'MINILOOK'],
            ['name' => 'BERMUDA BAGGY', 'category' => 'MINILOOK'],
            ['name' => 'BERMUDA SLIM', 'category' => 'MINILOOK'],
            ['name' => 'CHAQUETA LARGA', 'category' => 'MINILOOK'],
            ['name' => 'CHAQUETA MEDIA', 'category' => 'MINILOOK'],
            ['name' => 'CHAQUETA CORTA', 'category' => 'MINILOOK'],
            ['name' => 'TOP CORTO', 'category' => 'MINILOOK'],
            ['name' => 'TOP MEDIO', 'category' => 'MINILOOK'],
            ['name' => 'CROPPED', 'category' => 'MODA'],
            ['name' => 'PALAZZO', 'category' => 'MODA'],
            ['name' => 'STOVEPIPE', 'category' => 'MODA'],
            ['name' => 'WIDE LEG', 'category' => 'MODA'],
            ['name' => 'VESTIDO CORTO', 'category' => 'MONOLOOK'],
            ['name' => 'VESTIDO MEDIO', 'category' => 'MONOLOOK'],
            ['name' => 'VESTIDO LARGO', 'category' => 'MONOLOOK'],
            ['name' => 'BRAGA CORTA', 'category' => 'MONOLOOK'],
            ['name' => 'BRAGA MEDIA', 'category' => 'MONOLOOK'],
            ['name' => 'BRAGA LARGA', 'category' => 'MONOLOOK'],
            ['name' => 'JUMPER CORTA', 'category' => 'MONOLOOK'],
            ['name' => 'JUMPER MEDIA', 'category' => 'MONOLOOK'],
            ['name' => 'JUMPER LARGA', 'category' => 'MONOLOOK'],
        ];

        foreach ($subcategories as $data) {

            $subcategory = Subcategory::create([
                'name' => $data['name'],
                'description' => $data['name'],
            ]);

            if (isset($categories[$data['category']])) {
                $subcategory->category()->sync(
                    [$categories[$data['category']]->id]
                );
            }
        }

        $item = new Item();
        $item->name = 'Grupos';
        $item->description = 'Listado de grupos.';
        $item->save();

        $groups = [];

        $groups['NO APLICA'] = Group::create(['name' => 'NO APLICA', 'description' => 'NO APLICA']);
        $groups['DAMA'] = Group::create(['name' => 'DAMA', 'description' => 'DAMA']);
        $groups['CABALLERO'] = Group::create(['name' => 'CABALLERO', 'description' => 'CABALLERO']);
        $groups['JUVENIL DAMA'] = Group::create(['name' => 'JUVENIL DAMA', 'description' => 'JUVENIL DAMA']);
        $groups['JUVENIL CABALLERO'] = Group::create(['name' => 'JUVENIL CABALLERO', 'description' => 'JUVENIL CABALLERO']);
        $groups['NIÑA'] = Group::create(['name' => 'NIÑA', 'description' => 'NIÑA']);
        $groups['NIÑO'] = Group::create(['name' => 'NIÑO', 'description' => 'NIÑO']);

        $item = new Item();
        $item->name = 'Subgrupos';
        $item->description = 'Listado de subgrupos.';
        $item->save();

        $subcategory = new Subgroup();
        $subcategory->name = 'No aplica';
        $subcategory->description = 'No aplica';
        $subcategory->save();

        $item = new Item();
        $item->name = 'Marcas';
        $item->description = 'Listado de marcas.';
        $item->save();

        $trademarks = [
            [
                'name' => 'BLESS ORIGINAL',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^(05[0-9]{6}|BO[0-9]{6})$/',
                        'message' => 'Debe iniciar con 05 o BO y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ]
                ]
            ],
            [
                'name' => 'BLESS',
                'group' => 'DAMA',
                'validations' => [
                    [
                        'id' => 1,
                        'regex' => '/^(5[0-9]{6}|BL[0-9]{6})$/',
                        'message' => 'Debe iniciar con 5 o BL y tener 6 números',
                        'created_at' => now(),
                        'updated_at' => null,
                        'deleted_at' => null,
                    ]
                ]
            ],
            ['name' => 'BLESS 23', 'group' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(8[0-9]{6}|BS[0-9]{6})$/',
                    'message' => 'Debe iniciar con 8 o BS y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'BLESS JUNIOR', 'group' => 'JUVENIL DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(BJ[0-9]{6})$/',
                    'message' => 'Debe iniciar con BJ y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(7[0-9]{6}|ZA[0-9]{6})$/',
                    'message' => 'Debe iniciar con 7 o ZA y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH PREMIUM', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(P7[0-9]{6}|ZP[0-9]{6})$/',
                    'message' => 'Debe iniciar con P7 o ZP y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH CURVI', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(BJ[0-9]{6}|ZC[0-9]{6})$/',
                    'message' => 'Debe iniciar con BJ o ZC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH TEENS', 'group' => 'JUVENIL DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(4[0-9]{6}|ZT[0-9]{6})$/',
                    'message' => 'Debe iniciar con 4 o ZT y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'ZARETH REBELS', 'group' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(R4[0-9]{6}|ZR[0-9]{6})$/',
                    'message' => 'Debe iniciar con R4 o ZR y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'CALIFORNIA DAMA', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(CC[0-9]{6}|CD[0-9]{6})$/',
                    'message' => 'Debe iniciar con CC o CD y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'CALIFORNIA CABALLERO', 'group' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(CRC[0-9]{6}|CC[0-9]{6})$/',
                    'message' => 'Debe iniciar con CRC o CC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'CALIFORNIA KIDS', 'group' => 'NIÑO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(CK[0-9]{6})$/',
                    'message' => 'Debe iniciar con CK y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'MICHELL VILLAMIZAR', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(MV[0-9]{6})$/',
                    'message' => 'Debe iniciar con MV y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'MICHELL PLUS', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(MPV[0-9]{6}|MP[0-9]{6})$/',
                    'message' => 'Debe iniciar con MPV oMP y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEW YORK DAMA', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(YD[0-9]{6})$/',
                    'message' => 'Debe iniciar con YD y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEW YORK KIDS', 'group' => 'NIÑO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(YK[0-9]{6})$/',
                    'message' => 'Debe iniciar con YK y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEW YORK CABALLERO', 'group' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(YC[0-9]{6})$/',
                    'message' => 'Debe iniciar con YC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'SHIREL CLASSIC', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(C9[0-9]{6}|SC[0-9]{6})$/',
                    'message' => 'Debe iniciar con C9 o SC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'SHIREL', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(9[0-9]{6}|SH[0-9]{6})$/',
                    'message' => 'Debe iniciar con 9 o SH y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'SHIREL PLUS', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(SP[0-9]{6})$/',
                    'message' => 'Debe iniciar con SP y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'DHARA', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(DH[0-9]{6})$/',
                    'message' => 'Debe iniciar con DH y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'STARA DENIM', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(STDM[0-9]{6}|SD[0-9]{6})$/',
                    'message' => 'Debe iniciar con STDM o SD y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'STARA MEN', 'group' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(SM[0-9]{6})$/',
                    'message' => 'Debe iniciar con SM y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'STARA', 'group' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(STA[0-9]{6}|ST[0-9]{6})$/',
                    'message' => 'Debe iniciar con STA o ST y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEON', 'group' => 'JUVENIL CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(N[0-9]{6}|NE[0-9]{6})$/',
                    'message' => 'Debe iniciar con N o NE y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'NEON KIDS', 'group' => 'NIÑO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(NK[0-9]{6})$/',
                    'message' => 'Debe iniciar con NK y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'LOA CABALLERO', 'group' => 'CABALLERO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(LC[0-9]{6})$/',
                    'message' => 'Debe iniciar con LC y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'LOA DAMA', 'group' => 'DAMA', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(LD[0-9]{6})$/',
                    'message' => 'Debe iniciar con LD y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]],
            ['name' => 'LOA KIDS', 'group' => 'NIÑO', 'validations' => [
                [
                    'id' => 1,
                    'regex' => '/^(LK[0-9]{6})$/',
                    'message' => 'Debe iniciar con LK y tener 6 números',
                    'created_at' => now(),
                    'updated_at' => null,
                    'deleted_at' => null,
                ]
            ]]
        ];

        foreach ($trademarks as $data) {

            $trademark = Trademark::create([
                'name' => $data['name'],
                'description' => $data['name'],
                'settings' => [
                    'validations' => $data['validations'] ?? []
                ]
            ]);

            if (isset($groups[$data['group']])) {
                $trademark->auditSync(
                    'group',
                    [$groups[$data['group']]->id]
                );
            }
        }

        $item = new Item();
        $item->name = 'Tallas';
        $item->description = 'Listado de tallas.';
        $item->settings = (object) [
            'finished_product' => (object) [
                'name' => 'regex:/^(?:\d[02468]|XS|S|M|L|XL|XXL)$/',
                'description' => 'regex:/^T\d{2,}$/'
            ]
        ];
        $item->save();

        $size = new Size();
        $size->name = 'No aplica';
        $size->description = 'No aplica';
        $size->save();

        $item = new Item();
        $item->name = 'Tipos de Prenda';
        $item->description = 'Listado de tipos de prenda.';
        $item->save();

        $garment_types = [
            'NO APLICA',
            'CLASICO',
            'SEMICLASICO',
            'TIPO 1',
            'TIPO 2',
            'TIPO 3',
            'MODA',
            'SEMIMODA',
        ];

        foreach ($garment_types as $name) {
            GarmentType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        $item = new Item();
        $item->name = 'Tipos de Bota';
        $item->description = 'Listado de tipos de bota.';
        $item->save();

        $boot_types = [
            'AJUSTADA',
            'ANCHA',
            'ANCHA NORMAL',
            'BOTA CORDON CAUCHO',
            'CAMPANA',
            'CAMPANA FLECO',
            'DOBLADA',
            'ENCAUCHADA',
            'FLECO',
            'GUARDA POLVO',
            'NO APLICA',
            'NORMAL',
            'NORMAL CIERRE',
            'RECTA',
            'RECTA DESFLECADA',
            'RECTA NORMAL',
            'SLIM',
            'SOLTAR',
        ];

        foreach ($boot_types as $name) {
            BootType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        $item = new Item();
        $item->name = 'Tipos de Cotilla';
        $item->description = 'Listado de tipos de cotilla.';
        $item->save();

        $yoke_types = [
            'NO APLICA',
            'CERRADORA',
            'DOBLE COTILLA',
            'SOBREPUESTA',
            'FILETE',
        ];

        foreach ($yoke_types as $name) {
            YokeType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        $item = new Item();
        $item->name = 'Tipos de Trasero';
        $item->description = 'Listado de tipos de trasero.';
        $item->save();

        $back_types = [
            'NO APLICA',
            'BOLSILLO',
            'COTILLA',
            'BOLSILLO RIBETE',
            'RIBETE COTILLA',
        ];

        foreach ($back_types as $name) {
            BackType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        $item = new Item();
        $item->name = 'Tipos de Pretina';
        $item->description = 'Listado de tipos de pretina.';
        $item->save();

        $waistband_types = [
            'NO APLICA',
            '1 BOTÓN',
            '2 BOTÓN',
            '3 BOTÓN',
            '4 BOTÓN',
        ];

        foreach ($waistband_types as $name) {
            WaistbandType::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        $item = new Item();
        $item->name = 'Colores';
        $item->description = 'Listado de colores.';
        $item->save();

        $colors = [
            'SIN COLOR',
            'AZUL PANTONE',
            'SALMON',
            'ANIMAL PRINT',
            'UVA',
            'MANDARINA',
            'ESTAMPADO',
            'CAMEL',
            'MILITAR',
            'AZUL REY',
            'VERDE MILITAR',
            'VINOTINTO',
            'PALOROSA',
            'AZUL',
            'AZUL CIELO',
            'VERDE OLIVA',
            'CAFE LECHE',
            'BLANCO',
            'NEGRO',
            'GRIS',
            'AZUL PETROLEO',
            'CREMA',
            'CAQUI OSCURO',
            'MOSTAZA',
            'VERDE OSCURO',
            'HUESO',
            'BEIGE',
            'LILA',
            'VIOLETA',
            'FUCSIA',
            'CLARO',
            'MEDIO OSUCRO',
            'MEDIO',
            'MEDIO CLARO',
            'OSCURO',
            'OSCURO FIJACIÓN',
            'COLOR',
            'GRIS OSCURO',
            'GRIS CLARO',
            'CLARO DIRTY',
            'MEDIO DIRTY',
            'MEDIO OSCURO DIRTY',
            'OSCURO DIRTY',
            'OSCURO RESINA',
        ];

        foreach ($colors as $name) {
            Color::create([
                'name' => $name,
                'description' => $name,
            ]);
        }

        $item = new Item();
        $item->name = 'Tonos de Lavado';
        $item->description = 'Listado de tonos de lavado.';
        $item->save();

        $wash_tones = [
            'HIELO',
            'BLANCO',
            'COLOR',
            'CLARO',
            'MEDIO',
            'GRIS',
            'OSCURO PD',
            'NEGRO',
            'MEDIO CLARO',
            'MEDIO OSCURO',
            'CLARO DIRTY',
            'MEDIO DIRTY',
            'OSCURO JEAN',
            'OSCURO DIRTY',
            'FIJACION',
            'GRIS OSCURO',
            'GRIS CLARO',
            'MEDIO OSCURO DIRTY',
            'OSCURO RESINA',
        ];

        foreach ($wash_tones as $name) {
            WashTone::create([
                'name' => $name,
                'description' => $name,
            ]);
        }


        $item = new Item();
        $item->name = 'Procesos';
        $item->description = 'Listado de procesos.';
        $item->save();

        $processes = [
            ['name' => 'DISEÑO', 'subprocesses' => false],
            ['name' => 'CORTE', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'PREPARACION', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'SATELITE', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'CONFECCION', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'ENSAMBLE', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'PRETINTORERIA', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'LAVANDERIA', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'TERMINACIÓN', 'subprocesses' => false, 'in_technical_sheet' => true],
            ['name' => 'BODEGA', 'subprocesses' => false],
        ];

        foreach ($processes as $data) {

            Process::create([
                'name' => $data['name'],
                'description' => $data['name'],
                'settings' => [
                    'subprocesses' => $data['subprocesses'] ?? [],
                    'in_technical_sheet' => $data['in_technical_sheet'] ?? false
                ]
            ]);
        }

        $item = new Item();
        $item->name = 'Subprocesos';
        $item->description = 'Listado de subprocesos.';
        $item->save();

        $subprocess = new Subprocess();
        $subprocess->name = 'No aplica';
        $subprocess->description = 'No aplica';
        $subprocess->save();

        $item = new Item();
        $item->name = 'Operaciones';
        $item->description = 'Listado de operaciones.';
        $item->save();

        $operation = new Operation();
        $operation->name = 'No aplica';
        $operation->description = 'No aplica';
        $operation->save();

        $item = new Item();
        $item->name = 'Proveedores';
        $item->description = 'Listado de proveedores.';
        $item->save();

        $supplier = new Supplier();
        $supplier->name = 'No aplica';
        $supplier->description = 'No aplica';
        $supplier->save();

        $item = new Item();
        $item->name = 'Tipos de Insumo';
        $item->description = 'Listado de tipos de insumo.';
        $item->save();

        $supply_types = [];

        $supply_types['NO APLICA'] = SupplyType::create(['name' => 'NO APLICA', 'description' => 'NO APLICA', 'settings' => [
            'in_technical_sheet' => false
        ]]);
        $supply_types['TELA'] = SupplyType::create(['name' => 'TELA', 'description' => 'TELA', 'settings' => [
            'in_technical_sheet' => true,
            'has_variants' => true
        ]]);
        $supply_types['HILO'] = SupplyType::create(['name' => 'HILO', 'description' => 'HILO', 'settings' => [
            'in_technical_sheet' => true,
            'has_variants' => true
        ]]);

        $item = new Item();
        $item->name = 'Variantes';
        $item->description = 'Listado de variantes.';
        $item->save();

        $variants = [
            ['name' => 'NO APLICA', 'supply_type' => 'NO APLICA',],
            ['name' => 'ROSAL', 'supply_type' => 'TELA',],
            ['name' => 'DOBLE', 'supply_type' => 'TELA',],
            ['name' => 'PASAR', 'supply_type' => 'TELA'],
            ['name' => 'NEGRO', 'supply_type' => 'HILO'],
        ];

        foreach ($variants as $data) {

            $variant = Variant::create([
                'name' => $data['name'],
                'description' => $data['name'],
            ]);

            if (isset($supply_types[$data['supply_type']])) {
                $variant->supply_type()->sync(
                    [$supply_types[$data['supply_type']]->id]
                );
            }
        }

        $item = new Item();
        $item->name = 'Colección';
        $item->description = 'Listado de colecciones.';
        $item->save();

        $collections = [
            [
                'name' => 'NO APLICA',
                'description' => 'NO APLICA',
                'settings' => [
                    'code' => 'NOAP',
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-12-31',
                ]
            ],
            [
                'name' => 'COLOMBIA MODA',
                'description' => 'COLOMBIA MODA',
                'settings' => [
                    'code' => 'C52026',
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-07-31',
                ]
            ],
            [
                'name' => 'VERANO URBANO',
                'description' => 'COLECCIÓN VERANO URBANO',
                'settings' => [
                    'code' => 'VU2026',
                    'start_date' => '2026-03-01',
                    'end_date' => '2026-05-31',
                ]
            ],
            [
                'name' => 'DENIM ESSENCE',
                'description' => 'COLECCIÓN DENIM ESSENCE',
                'settings' => [
                    'code' => 'DE2026',
                    'start_date' => '2026-06-01',
                    'end_date' => '2026-08-31',
                ]
            ],
            [
                'name' => 'OTOÑO ELEGANTE',
                'description' => 'COLECCIÓN OTOÑO ELEGANTE',
                'settings' => [
                    'code' => 'OE2026',
                    'start_date' => '2026-09-01',
                    'end_date' => '2026-11-30',
                ]
            ],
        ];

        foreach ($collections as $data) {
            Collection::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'settings' => $data['settings'],
            ]);
        }

        $item = new Item();
        $item->name = 'Piezas';
        $item->description = 'Listado de piezas.';
        $item->save();

        $clothing_line = new Piece();
        $clothing_line->name = 'No aplica';
        $clothing_line->description = 'No aplica';
        $clothing_line->save();

        $item = new Item();
        $item->name = 'Siluetas';
        $item->description = 'Listado de siluetas.';
        $item->save();

        $silhouette = new Silhouette();
        $silhouette->name = 'No aplica';
        $silhouette->description = 'No aplica';
        $silhouette->save();

        $item = new Item();
        $item->name = 'Tipos de Tela';
        $item->description = 'Listado de tipos de tela.';
        $item->save();

        $fabric_type = new FabricType();
        $fabric_type->name = 'No aplica';
        $fabric_type->description = 'No aplica';
        $fabric_type->save();

        $item = new Item();
        $item->name = 'Tipos de Hilo';
        $item->description = 'Listado de tipos de hilo.';
        $item->save();

        $thread_type = new ThreadType();
        $thread_type->name = 'No aplica';
        $thread_type->description = 'No aplica';
        $thread_type->save();
    }
}

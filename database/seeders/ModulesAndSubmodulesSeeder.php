<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Submodule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModulesAndSubmodulesSeeder extends Seeder
{
    public function run(): void
    {

        $modules_and_submodules = [
            [
                'module' => [
                    'name' => 'Configuración',
                    'icon' => 'FaCogs',
                ],

                'submodules' => [
                    [
                        'name' => 'Módulos',
                        'url' => '/modules',
                        'icon' => 'FaBuffer',
                        'permission_id' => 17
                    ],
                    [
                        'name' => 'Usuarios',
                        'url' => '/users',
                        'icon' => 'FaUserFriends',
                        'permission_id' => 1
                    ],
                    [
                        'name' => 'Personas',
                        'url' => '/people',
                        'icon' => 'FaUsers',
                        'permission_id' => 29
                    ],
                    [
                        'name' => 'Empleados',
                        'url' => '/employees',
                        'icon' => 'FaHospitalUser',
                        'permission_id' => 35
                    ],
                    [
                        'name' => 'Roles',
                        'url' => '/roles',
                        'icon' => 'FaShieldAlt',
                        'permission_id' => 9
                    ],
                    [
                        'name' => 'Permisos',
                        'url' => '/permissions',
                        'icon' => 'FaUserLock',
                        'permission_id' => 13
                    ],
                ]
            ],
            [
                'module' => [
                    'name' => 'Administración',
                    'icon' => 'FaToolbox',
                ],

                'submodules' => [
                    [
                        'name' => 'Áreas',
                        'url' => '/areas',
                        'icon' => 'FaMapMarkedAlt',
                        'permission_id' => 41
                    ],
                    [
                        'name' => 'Géneros',
                        'url' => '/genders',
                        'icon' => 'FaVenusMars',
                        'permission_id' => 55
                    ],
                    [
                        'name' => 'Tipos de Sangre',
                        'url' => '/blood_types',
                        'icon' => 'FaTint',
                        'permission_id' => 61
                    ],
                    [
                        'name' => 'Admin de Riesgos',
                        'url' => '/risk_managers',
                        'icon' => 'FaBuilding',
                        'permission_id' => 85
                    ],
                    [
                        'name' => 'Entidades de Salud',
                        'url' => '/health_entities',
                        'icon' => 'FaHospital',
                        'permission_id' => 79
                    ],
                    [
                        'name' => 'C. de Compensación',
                        'url' => '/compensation_funds',
                        'icon' => 'FaHandsHelping',
                        'permission_id' => 73
                    ],
                    [
                        'name' => 'Fondos de Pensión',
                        'url' => '/pension_funds',
                        'icon' => 'FaPiggyBank',
                        'permission_id' => 67
                    ],
                ]
            ],

            [
                'module' => [
                    'name' => 'Caracterizacion',
                    'icon' => 'FaTag',
                ],

                'submodules' => [
                    [
                        'name' => 'Marcas',
                        'url' => '/trademarks',
                        'icon' => 'FaTags',
                        'permission_id' => 91
                    ],
                    [
                        'name' => 'Tallas',
                        'url' => '/sizes',
                        'icon' => 'FaTape',
                        'permission_id' => 105
                    ],
                    [
                        'name' => 'Subgrupos',
                        'url' => '/subgroups',
                        'icon' => 'FaObjectGroup',
                        'permission_id' => 169
                    ],
                    [
                        'name' => 'Grupos',
                        'url' => '/groups',
                        'icon' => 'FaBoxes',
                        'permission_id' => 163
                    ],
                    [
                        'name' => 'Piezas',
                        'url' => '/pieces',
                        'icon' => 'FaStream',
                        'permission_id' => 112
                    ],
                    [
                        'name' => 'Tipos de Prenda',
                        'url' => '/garment_types',
                        'icon' => 'FaThList',
                        'permission_id' => 119
                    ],
                    [
                        'name' => 'Tipos de Bota',
                        'url' => '/boot_types',
                        'icon' => 'FaShoePrints',
                        'permission_id' => 126
                    ],
                    [
                        'name' => 'Tipo de Trasero',
                        'url' => '/back_types',
                        'icon' => 'FaBookmark',
                        'permission_id' => 133
                    ],
                    [
                        'name' => 'Tipos de Pretina',
                        'url' => '/waistband_types',
                        'icon' => 'FaGripLines',
                        'permission_id' => 140
                    ],
                    [
                        'name' => 'Categorías',
                        'url' => '/categories',
                        'icon' => 'FaListUI',
                        'permission_id' => 147
                    ],
                    [
                        'name' => 'Procesos',
                        'url' => '/processes',
                        'icon' => 'FaAlignLeft',
                        'permission_id' => 179
                    ],
                    [
                        'name' => 'Tonos de Lavado',
                        'url' => '/wash_tones',
                        'icon' => 'FaFillDrip',
                        'permission_id' => 191
                    ],
                    [
                        'name' => 'Colores',
                        'url' => '/colors',
                        'icon' => 'FaPalette',
                        'permission_id' => 197
                    ],
                    [
                        'name' => 'Colecciones',
                        'url' => '/collections',
                        'icon' => 'FaCubes',
                        'permission_id' => 203
                    ],
                    [
                        'name' => 'Tipos de Cotilla',
                        'url' => '/yoke_types',
                        'icon' => 'FaDraftingCompass',
                        'permission_id' => 209
                    ],
                    [
                        'name' => 'Proveedores',
                        'url' => '/suppliers',
                        'icon' => 'FaCampground',
                        'permission_id' => 215
                    ],
                    [
                        'name' => 'Tipos de Insumo',
                        'url' => '/supply_types',
                        'icon' => 'FaToolbox',
                        'permission_id' => 219
                    ],
                ]
            ],
            [
                'module' => [
                    'name' => 'Gestión',
                    'icon' => 'FaCogs',
                ],

                'submodules' => [
                    [
                        'name' => 'G. de Colecciones',
                        'url' => '/management/collections',
                        'icon' => 'FaFolder',
                        'permission_id' => 19
                    ],
                ]
            ],
        ];

        foreach ($modules_and_submodules as $item) {

            $module = Module::create([
                'name' => $item['module']['name'],
                'icon' => $item['module']['icon'],
            ]);

            foreach ($item['submodules'] as $submodule) {

                Submodule::create([
                    'module_id' => $module->id,
                    'name' => $submodule['name'],
                    'url' => $submodule['url'],
                    'icon' => $submodule['icon'],
                    'permission_id' => $submodule['permission_id'],
                ]);
            }
        }
    }
}

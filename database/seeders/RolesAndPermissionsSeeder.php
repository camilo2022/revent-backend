<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {

        $roles_and_permissions = [
            [
                'role' => [
                    'name' => 'users',
                    'title' => 'Usuarios',
                    'description' => 'Gestión de usuarios.',
                ],

                'permissions' => [
                    [
                        'name' => 'users.all',
                        'title' => 'Listar usuarios',
                        'description' => 'Permite listar todos los usuarios del sistema.',
                    ],
                    [
                        'name' => 'users.find',
                        'title' => 'Consultar usuario',
                        'description' => 'Permite consultar la información de un usuario específico.',
                    ],
                    [
                        'name' => 'users.store',
                        'title' => 'Crear usuario',
                        'description' => 'Permite crear nuevos usuarios en el sistema.',
                    ],
                    [
                        'name' => 'users.update',
                        'title' => 'Actualizar usuario',
                        'description' => 'Permite actualizar la información de un usuario.',
                    ],
                    [
                        'name' => 'users.delete',
                        'title' => 'Eliminar usuario',
                        'description' => 'Permite eliminar usuarios del sistema.',
                    ],
                    [
                        'name' => 'users.restore',
                        'title' => 'Restaurar usuario',
                        'description' => 'Permite restaurar usuarios eliminados.',
                    ],
                    [
                        'name' => 'users.authorization.assign',
                        'title' => 'Asignar roles o permisos',
                        'description' => 'Permite asignar roles o permisos a los usuarios.',
                    ],
                    [
                        'name' => 'users.authorization.remove',
                        'title' => 'Remover roles o permisos',
                        'description' => 'Permite remover roles o permisos asignados a los usuarios.',
                    ],
                ]
            ],

            [
                'role' => [
                    'name' => 'authorization',
                    'title' => 'Autorización',
                    'description' => 'Gestión de roles y permisos del sistema.',
                ],

                'permissions' => [
                    [
                        'name' => 'authorization.roles.all',
                        'title' => 'Listar roles',
                        'description' => 'Permite listar todos los roles del sistema.',
                    ],
                    [
                        'name' => 'authorization.roles.find',
                        'title' => 'Consultar rol',
                        'description' => 'Permite consultar la información de un rol específico.',
                    ],
                    [
                        'name' => 'authorization.roles.store',
                        'title' => 'Crear rol',
                        'description' => 'Permite crear nuevos roles en el sistema.',
                    ],
                    [
                        'name' => 'authorization.roles.update',
                        'title' => 'Actualizar rol',
                        'description' => 'Permite actualizar la información de un rol.',
                    ],
                    [
                        'name' => 'authorization.permissions.all',
                        'title' => 'Listar permisos',
                        'description' => 'Permite listar todos los permisos del sistema.',
                    ],
                    [
                        'name' => 'authorization.permissions.find',
                        'title' => 'Consultar permiso',
                        'description' => 'Permite consultar la información de un permiso específico.',
                    ],
                    [
                        'name' => 'authorization.permissions.store',
                        'title' => 'Crear permiso',
                        'description' => 'Permite crear nuevos permisos en el sistema.',
                    ],
                    [
                        'name' => 'authorization.permissions.update',
                        'title' => 'Actualizar permiso',
                        'description' => 'Permite actualizar la información de un permiso.',
                    ],
                ]
            ],

            [
                'role' => [
                    'name' => 'navegation',
                    'title' => 'Navegación',
                    'description' => 'Gestión de módulos y submódulos del sistema.',
                ],

                'permissions' => [
                    [
                        'name' => 'navegation.modules.all',
                        'title' => 'Listar módulos',
                        'description' => 'Permite listar todos los módulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.find',
                        'title' => 'Consultar módulo',
                        'description' => 'Permite consultar la información de un módulo específico.',
                    ],
                    [
                        'name' => 'navegation.modules.store',
                        'title' => 'Crear módulo',
                        'description' => 'Permite crear nuevos módulos en el sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.update',
                        'title' => 'Actualizar módulo',
                        'description' => 'Permite actualizar la información de un módulo.',
                    ],
                    [
                        'name' => 'navegation.modules.delete',
                        'title' => 'Eliminar módulo',
                        'description' => 'Permite eliminar módulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.restore',
                        'title' => 'Restaurar módulo',
                        'description' => 'Permite restaurar módulos eliminados.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.all',
                        'title' => 'Listar submódulos',
                        'description' => 'Permite listar todos los submódulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.find',
                        'title' => 'Consultar submódulo',
                        'description' => 'Permite consultar la información de un submódulo específico.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.store',
                        'title' => 'Crear submódulo',
                        'description' => 'Permite crear nuevos submódulos en el sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.update',
                        'title' => 'Actualizar submódulo',
                        'description' => 'Permite actualizar la información de un submódulo.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.delete',
                        'title' => 'Eliminar submódulo',
                        'description' => 'Permite eliminar submódulos del sistema.',
                    ],
                    [
                        'name' => 'navegation.modules.submodules.restore',
                        'title' => 'Restaurar submódulo',
                        'description' => 'Permite restaurar submódulos eliminados.',
                    ],
                ]
            ],
            [
                'role' => [
                    'name' => 'people',
                    'title' => 'Personas',
                    'description' => 'Gestión de personas.',
                ],
                'permissions' => [
                    ['name' => 'people.all', 'title' => 'Listar personas', 'description' => 'Permite listar todas las personas.'],
                    ['name' => 'people.find', 'title' => 'Consultar persona', 'description' => 'Permite consultar una persona.'],
                    ['name' => 'people.store', 'title' => 'Crear persona', 'description' => 'Permite crear personas.'],
                    ['name' => 'people.update', 'title' => 'Actualizar persona', 'description' => 'Permite actualizar personas.'],
                    ['name' => 'people.delete', 'title' => 'Eliminar persona', 'description' => 'Permite eliminar personas.'],
                    ['name' => 'people.restore', 'title' => 'Restaurar persona', 'description' => 'Permite restaurar personas.'],
                ]
            ],
            [
                'role' => [
                    'name' => 'employees',
                    'title' => 'Empleados',
                    'description' => 'Gestión de empleados.',
                ],
                'permissions' => [
                    ['name' => 'employees.all', 'title' => 'Listar empleados', 'description' => 'Permite listar empleados.'],
                    ['name' => 'employees.find', 'title' => 'Consultar empleado', 'description' => 'Permite consultar un empleado.'],
                    ['name' => 'employees.store', 'title' => 'Crear empleado', 'description' => 'Permite crear empleados.'],
                    ['name' => 'employees.update', 'title' => 'Actualizar empleado', 'description' => 'Permite actualizar empleados.'],
                    ['name' => 'employees.delete', 'title' => 'Eliminar empleado', 'description' => 'Permite eliminar empleados.'],
                    ['name' => 'employees.restore', 'title' => 'Restaurar empleado', 'description' => 'Permite restaurar empleados.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'organizational_structure',
                    'title' => 'Estructura organizacional',
                    'description' => 'Gestión de áreas y cargos del sistema.',
                ],
                'permissions' => [
                    ['name' => 'organizational_structure.areas.all', 'title' => 'Listar áreas', 'description' => 'Permite listar áreas.'],
                    ['name' => 'organizational_structure.areas.find', 'title' => 'Consultar área', 'description' => 'Permite consultar un área.'],
                    ['name' => 'organizational_structure.areas.store', 'title' => 'Crear área', 'description' => 'Permite crear áreas.'],
                    ['name' => 'organizational_structure.areas.update', 'title' => 'Actualizar área', 'description' => 'Permite actualizar áreas.'],
                    ['name' => 'organizational_structure.areas.delete', 'title' => 'Eliminar área', 'description' => 'Permite eliminar áreas.'],
                    ['name' => 'organizational_structure.areas.restore', 'title' => 'Restaurar área', 'description' => 'Permite restaurar áreas.'],
                    ['name' => 'organizational_structure.areas.positions.all', 'title' => 'Listar cargos', 'description' => 'Permite listar cargos.'],
                    ['name' => 'organizational_structure.areas.positions.find', 'title' => 'Consultar cargo', 'description' => 'Permite consultar un cargo.'],
                    ['name' => 'organizational_structure.areas.positions.store', 'title' => 'Crear cargo', 'description' => 'Permite crear cargos.'],
                    ['name' => 'organizational_structure.areas.positions.update', 'title' => 'Actualizar cargo', 'description' => 'Permite actualizar cargos.'],
                    ['name' => 'organizational_structure.areas.positions.delete', 'title' => 'Eliminar cargo', 'description' => 'Permite eliminar cargos.'],
                    ['name' => 'organizational_structure.areas.positions.restore', 'title' => 'Restaurar cargo', 'description' => 'Permite restaurar cargos.'],
                    ['name' => 'organizational_structure.areas.positions.authorization.assign', 'title' => 'Asignar permisos', 'description' => 'Permite asignar permisos a cargos.'],
                    ['name' => 'organizational_structure.areas.positions.authorization.remove', 'title' => 'Remover permisos', 'description' => 'Permite remover permisos de cargos.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'genders',
                    'title' => 'Géneros',
                    'description' => 'Gestión de géneros.',
                ],
                'permissions' => [
                    ['name' => 'genders.all', 'title' => 'Listar géneros', 'description' => 'Permite listar géneros.'],
                    ['name' => 'genders.find', 'title' => 'Consultar género', 'description' => 'Permite consultar género.'],
                    ['name' => 'genders.store', 'title' => 'Crear género', 'description' => 'Permite crear géneros.'],
                    ['name' => 'genders.update', 'title' => 'Actualizar género', 'description' => 'Permite actualizar géneros.'],
                    ['name' => 'genders.delete', 'title' => 'Eliminar género', 'description' => 'Permite eliminar géneros.'],
                    ['name' => 'genders.restore', 'title' => 'Restaurar género', 'description' => 'Permite restaurar géneros.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'blood_types',
                    'title' => 'Tipos de sangre',
                    'description' => 'Gestión de tipos de sangre.',
                ],
                'permissions' => [
                    ['name' => 'blood_types.all', 'title' => 'Listar tipos de sangre', 'description' => 'Permite listar tipos de sangre.'],
                    ['name' => 'blood_types.find', 'title' => 'Consultar tipo de sangre', 'description' => 'Permite consultar tipo de sangre.'],
                    ['name' => 'blood_types.store', 'title' => 'Crear tipo de sangre', 'description' => 'Permite crear tipos de sangre.'],
                    ['name' => 'blood_types.update', 'title' => 'Actualizar tipo de sangre', 'description' => 'Permite actualizar tipos de sangre.'],
                    ['name' => 'blood_types.delete', 'title' => 'Eliminar tipo de sangre', 'description' => 'Permite eliminar tipos de sangre.'],
                    ['name' => 'blood_types.restore', 'title' => 'Restaurar tipo de sangre', 'description' => 'Permite restaurar tipos de sangre.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'pension_funds',
                    'title' => 'Fondos de pensión',
                    'description' => 'Gestión de fondos de pensión.',
                ],
                'permissions' => [
                    ['name' => 'pension_funds.all', 'title' => 'Listar fondos', 'description' => 'Permite listar fondos.'],
                    ['name' => 'pension_funds.find', 'title' => 'Consultar fondo', 'description' => 'Permite consultar fondo.'],
                    ['name' => 'pension_funds.store', 'title' => 'Crear fondo', 'description' => 'Permite crear fondos.'],
                    ['name' => 'pension_funds.update', 'title' => 'Actualizar fondo', 'description' => 'Permite actualizar fondos.'],
                    ['name' => 'pension_funds.delete', 'title' => 'Eliminar fondo', 'description' => 'Permite eliminar fondos.'],
                    ['name' => 'pension_funds.restore', 'title' => 'Restaurar fondo', 'description' => 'Permite restaurar fondos.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'compensation_funds',
                    'title' => 'Cajas de compensación',
                    'description' => 'Gestión de cajas de compensación.',
                ],
                'permissions' => [
                    ['name' => 'compensation_funds.all', 'title' => 'Listar cajas', 'description' => 'Permite listar cajas.'],
                    ['name' => 'compensation_funds.find', 'title' => 'Consultar caja', 'description' => 'Permite consultar caja.'],
                    ['name' => 'compensation_funds.store', 'title' => 'Crear caja', 'description' => 'Permite crear cajas.'],
                    ['name' => 'compensation_funds.update', 'title' => 'Actualizar caja', 'description' => 'Permite actualizar cajas.'],
                    ['name' => 'compensation_funds.delete', 'title' => 'Eliminar caja', 'description' => 'Permite eliminar cajas.'],
                    ['name' => 'compensation_funds.restore', 'title' => 'Restaurar caja', 'description' => 'Permite restaurar cajas.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'health_entities',
                    'title' => 'Entidades de Salud',
                    'description' => 'Gestión de Entidades de Salud.',
                ],
                'permissions' => [
                    ['name' => 'health_entities.all', 'title' => 'Listar Entidades de Salud', 'description' => 'Permite listar Entidades de Salud.'],
                    ['name' => 'health_entities.find', 'title' => 'Consultar Entidades de Salud', 'description' => 'Permite consultar Entidades de Salud.'],
                    ['name' => 'health_entities.store', 'title' => 'Crear Entidades de Salud', 'description' => 'Permite crear Entidades de Salud.'],
                    ['name' => 'health_entities.update', 'title' => 'Actualizar Entidades de Salud', 'description' => 'Permite actualizar Entidades de Salud.'],
                    ['name' => 'health_entities.delete', 'title' => 'Eliminar Entidades de Salud', 'description' => 'Permite eliminar Entidades de Salud.'],
                    ['name' => 'health_entities.restore', 'title' => 'Restaurar Entidades de Salud', 'description' => 'Permite restaurar Entidades de Salud.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'risk_managers',
                    'title' => 'Admininistradoras de riesgos',
                    'description' => 'Gestión de Administradoras de riesgos.',
                ],
                'permissions' => [
                    ['name' => 'risk_managers.all', 'title' => 'Listar Administradoras de riesgos.', 'description' => 'Permite listar Administradoras de riesgos..'],
                    ['name' => 'risk_managers.find', 'title' => 'Consultar Administradoras de riesgos.', 'description' => 'Permite consultar Administradoras de riesgos..'],
                    ['name' => 'risk_managers.store', 'title' => 'Crear Administradoras de riesgos.', 'description' => 'Permite crear Administradoras de riesgos..'],
                    ['name' => 'risk_managers.update', 'title' => 'Actualizar Administradoras de riesgos.', 'description' => 'Permite actualizar Administradoras de riesgos..'],
                    ['name' => 'risk_managers.delete', 'title' => 'Eliminar Administradoras de riesgos.', 'description' => 'Permite eliminar Administradoras de riesgos..'],
                    ['name' => 'risk_managers.restore', 'title' => 'Restaurar Administradoras de riesgos.', 'description' => 'Permite restaurar Administradoras de riesgos..'],
                ]
            ],

            [
                'role' => [
                    'name' => 'trademarks',
                    'title' => 'Marcas de la Empresa',
                    'description' => 'Gestión de marcas de la empresa.',
                ],
                'permissions' => [
                    ['name' => 'trademarks.all', 'title' => 'Listar marcas.', 'description' => 'Permite listar marcas.'],
                    ['name' => 'trademarks.find', 'title' => 'Consultar marcas.', 'description' => 'Permite consultar marcas.'],
                    ['name' => 'trademarks.store', 'title' => 'Crear marcas.', 'description' => 'Permite crear marcas.'],
                    ['name' => 'trademarks.update', 'title' => 'Actualizar marcas.', 'description' => 'Permite actualizar marcas.'],
                    ['name' => 'trademarks.delete', 'title' => 'Eliminar marcas.', 'description' => 'Permite eliminar marcas.'],
                    ['name' => 'trademarks.restore', 'title' => 'Restaurar marcas.', 'description' => 'Permite restaurar marcas.'],
                    ['name' => 'trademarks.settings', 'title' => 'Configurar marcas.', 'description' => 'Permite configurar marcas.'],
                    ['name' => 'trademarks.size.assign', 'title' => 'Asignar una talla a una marca.', 'description' => 'Permite asignar tallas a marcas.'],
                    ['name' => 'trademarks.size.remove', 'title' => 'Remover una talla a una marca.', 'description' => 'Permite remover tallas a marcas.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'silhouettes',
                    'title' => 'Siluetas',
                    'description' => 'Gestión de siluetas de la empresa.',
                ],
                'permissions' => [
                    ['name' => 'silhouettes.all', 'title' => 'Listar siluetas.', 'description' => 'Permite listar siluetas.'],
                    ['name' => 'silhouettes.find', 'title' => 'Consultar siluetas.', 'description' => 'Permite consultar siluetas.'],
                    ['name' => 'silhouettes.store', 'title' => 'Crear siluetas.', 'description' => 'Permite crear siluetas.'],
                    ['name' => 'silhouettes.update', 'title' => 'Actualizar siluetas.', 'description' => 'Permite actualizar siluetas.'],
                    ['name' => 'silhouettes.delete', 'title' => 'Eliminar siluetas.', 'description' => 'Permite eliminar siluetas.'],
                    ['name' => 'silhouettes.restore', 'title' => 'Restaurar siluetas.', 'description' => 'Permite restaurar siluetas.'],
                    ['name' => 'silhouettes.settings', 'title' => 'Configurar siluetas.', 'description' => 'Permite configurar siluetas.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'sizes',
                    'title' => 'Tallas',
                    'description' => 'Gestión de tallas de la empresa.',
                ],
                'permissions' => [
                    ['name' => 'sizes.all', 'title' => 'Listar tallas.', 'description' => 'Permite listar tallas.'],
                    ['name' => 'sizes.find', 'title' => 'Consultar tallas.', 'description' => 'Permite consultar tallas.'],
                    ['name' => 'sizes.store', 'title' => 'Crear tallas.', 'description' => 'Permite crear tallas.'],
                    ['name' => 'sizes.update', 'title' => 'Actualizar tallas.', 'description' => 'Permite actualizar tallas.'],
                    ['name' => 'sizes.delete', 'title' => 'Eliminar tallas.', 'description' => 'Permite eliminar tallas.'],
                    ['name' => 'sizes.restore', 'title' => 'Restaurar tallas.', 'description' => 'Permite restaurar tallas.'],
                    ['name' => 'sizes.settings', 'title' => 'Configurar tallas.', 'description' => 'Permite configurar tallas.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'piece',
                    'title' => 'Piezas',
                    'description' => 'Gestión de piezas.',
                ],
                'permissions' => [
                    ['name' => 'pieces.all', 'title' => 'Listar piezas.', 'description' => 'Permite listar piezas.'],
                    ['name' => 'pieces.find', 'title' => 'Consultar piezas.', 'description' => 'Permite consultar piezas.'],
                    ['name' => 'pieces.store', 'title' => 'Crear piezas.', 'description' => 'Permite crear piezas.'],
                    ['name' => 'pieces.update', 'title' => 'Actualizar piezas.', 'description' => 'Permite actualizar piezas.'],
                    ['name' => 'pieces.delete', 'title' => 'Eliminar piezas.', 'description' => 'Permite eliminar piezas.'],
                    ['name' => 'pieces.restore', 'title' => 'Restaurar piezas.', 'description' => 'Permite restaurar piezas.'],
                    ['name' => 'pieces.settings', 'title' => 'Configurar piezas.', 'description' => 'Permite configurar piezas.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'garment_types',
                    'title' => 'Tipos de Prenda',
                    'description' => 'Gestión de tipos de prenda.',
                ],
                'permissions' => [
                    ['name' => 'garment_types.all', 'title' => 'Listar tipos de prenda.', 'description' => 'Permite listar tipos de prenda.'],
                    ['name' => 'garment_types.find', 'title' => 'Consultar tipos de prenda.', 'description' => 'Permite consultar tipos de prenda.'],
                    ['name' => 'garment_types.store', 'title' => 'Crear tipos de prenda.', 'description' => 'Permite crear tipos de prenda.'],
                    ['name' => 'garment_types.update', 'title' => 'Actualizar tipos de prenda.', 'description' => 'Permite actualizar tipos de prenda.'],
                    ['name' => 'garment_types.delete', 'title' => 'Eliminar tipos de prenda.', 'description' => 'Permite eliminar tipos de prenda.'],
                    ['name' => 'garment_types.restore', 'title' => 'Restaurar tipos de prenda.', 'description' => 'Permite restaurar tipos de prenda.'],
                    ['name' => 'garment_types.settings', 'title' => 'Configurar tipos de prenda.', 'description' => 'Permite configurar tipos de prenda.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'boot_types',
                    'title' => 'Tipos de Bota',
                    'description' => 'Gestión de tipos de bota.',
                ],
                'permissions' => [
                    ['name' => 'boot_types.all', 'title' => 'Listar tipos de bota.', 'description' => 'Permite listar tipos de bota.'],
                    ['name' => 'boot_types.find', 'title' => 'Consultar tipos de bota.', 'description' => 'Permite consultar tipos de bota.'],
                    ['name' => 'boot_types.store', 'title' => 'Crear tipos de bota.', 'description' => 'Permite crear tipos de bota.'],
                    ['name' => 'boot_types.update', 'title' => 'Actualizar tipos de bota.', 'description' => 'Permite actualizar tipos de bota.'],
                    ['name' => 'boot_types.delete', 'title' => 'Eliminar tipos de bota.', 'description' => 'Permite eliminar tipos de bota.'],
                    ['name' => 'boot_types.restore', 'title' => 'Restaurar tipos de bota.', 'description' => 'Permite restaurar tipos de bota.'],
                    ['name' => 'boot_types.settings', 'title' => 'Configurar tipos de bota.', 'description' => 'Permite configurar tipos de bota.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'back_types',
                    'title' => 'Tipos de trasero',
                    'description' => 'Gestión de tipos de trasero.',
                ],
                'permissions' => [
                    ['name' => 'back_types.all', 'title' => 'Listar tipos de trasero.', 'description' => 'Permite listar tipos de trasero.'],
                    ['name' => 'back_types.find', 'title' => 'Consultar tipos de trasero.', 'description' => 'Permite consultar tipos de trasero.'],
                    ['name' => 'back_types.store', 'title' => 'Crear tipos de trasero.', 'description' => 'Permite crear tipos de trasero.'],
                    ['name' => 'back_types.update', 'title' => 'Actualizar tipos de trasero.', 'description' => 'Permite actualizar tipos de trasero.'],
                    ['name' => 'back_types.delete', 'title' => 'Eliminar tipos de trasero.', 'description' => 'Permite eliminar tipos de trasero.'],
                    ['name' => 'back_types.restore', 'title' => 'Restaurar tipos de trasero.', 'description' => 'Permite restaurar tipos de trasero.'],
                    ['name' => 'back_types.settings', 'title' => 'Configurar tipos de trasero.', 'description' => 'Permite configurar tipos de trasero.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'waistband_types',
                    'title' => 'Tipos de pretina',
                    'description' => 'Gestión de tipos de pretina.',
                ],
                'permissions' => [
                    ['name' => 'waistband_types.all', 'title' => 'Listar tipos de pretina.', 'description' => 'Permite listar tipos de pretina.'],
                    ['name' => 'waistband_types.find', 'title' => 'Consultar tipos de pretina.', 'description' => 'Permite consultar tipos de pretina.'],
                    ['name' => 'waistband_types.store', 'title' => 'Crear tipos de pretina.', 'description' => 'Permite crear tipos de pretina.'],
                    ['name' => 'waistband_types.update', 'title' => 'Actualizar tipos de pretina.', 'description' => 'Permite actualizar tipos de pretina.'],
                    ['name' => 'waistband_types.delete', 'title' => 'Eliminar tipos de pretina.', 'description' => 'Permite eliminar tipos de pretina.'],
                    ['name' => 'waistband_types.restore', 'title' => 'Restaurar tipos de pretina.', 'description' => 'Permite restaurar tipos de pretina.'],
                    ['name' => 'waistband_types.settings', 'title' => 'Configurar tipos de pretina.', 'description' => 'Permite configurar tipos de pretina.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'categorization',
                    'title' => 'Categorización',
                    'description' => 'Gestión de categorías y subcategorías',
                ],
                'permissions' => [
                    ['name' => 'categorization.categories.all', 'title' => 'Listar categorías.', 'description' => 'Permite listar categorías.'],
                    ['name' => 'categorization.categories.find', 'title' => 'Consultar categorías.', 'description' => 'Permite consultar categorías.'],
                    ['name' => 'categorization.categories.store', 'title' => 'Crear categorías.', 'description' => 'Permite crear categorías.'],
                    ['name' => 'categorization.categories.update', 'title' => 'Actualizar categorías.', 'description' => 'Permite actualizar categorías.'],
                    ['name' => 'categorization.categories.delete', 'title' => 'Eliminar categorías.', 'description' => 'Permite eliminar categorías.'],
                    ['name' => 'categorization.categories.restore', 'title' => 'Restaurar categorías.', 'description' => 'Permite restaurar categorías.'],
                    ['name' => 'categorization.categories.settings', 'title' => 'Configurar categorías.', 'description' => 'Permite configurar categorías.'],
                    ['name' => 'categorization.categories.subcategories.all', 'title' => 'Listar subcategorías.', 'description' => 'Permite listar subcategorías.'],
                    ['name' => 'categorization.categories.subcategories.find', 'title' => 'Consultar subcategorías.', 'description' => 'Permite consultar subcategorías.'],
                    ['name' => 'categorization.categories.subcategories.store', 'title' => 'Crear subcategorías.', 'description' => 'Permite crear subcategorías.'],
                    ['name' => 'categorization.categories.subcategories.update', 'title' => 'Actualizar subcategorías.', 'description' => 'Permite actualizar subcategorías.'],
                    ['name' => 'categorization.categories.subcategories.delete', 'title' => 'Eliminar subcategorías.', 'description' => 'Permite eliminar subcategorías.'],
                    ['name' => 'categorization.categories.subcategories.restore', 'title' => 'Restaurar subcategorías.', 'description' => 'Permite restaurar subcategorías.'],
                    ['name' => 'categorization.categories.subcategories.settings', 'title' => 'Configurar subcategorías.', 'description' => 'Permite configurar subcategorías.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'classification',
                    'title' => 'Clasificación',
                    'description' => 'Gestión de grupos y subgrupos',
                ],
                'permissions' => [
                    ['name' => 'classification.groups.all', 'title' => 'Listar grupos.', 'description' => 'Permite listar grupos.'],
                    ['name' => 'classification.groups.find', 'title' => 'Consultar grupos.', 'description' => 'Permite consultar grupos.'],
                    ['name' => 'classification.groups.store', 'title' => 'Crear grupos.', 'description' => 'Permite crear grupos.'],
                    ['name' => 'classification.groups.update', 'title' => 'Actualizar grupos.', 'description' => 'Permite actualizar grupos.'],
                    ['name' => 'classification.groups.delete', 'title' => 'Eliminar grupos.', 'description' => 'Permite eliminar grupos.'],
                    ['name' => 'classification.groups.restore', 'title' => 'Restaurar grupos.', 'description' => 'Permite restaurar grupos.'],
                    ['name' => 'classification.subgroups.all', 'title' => 'Listar subgrupos.', 'description' => 'Permite listar subgrupos.'],
                    ['name' => 'classification.subgroups.find', 'title' => 'Consultar subgrupos.', 'description' => 'Permite consultar subgrupos.'],
                    ['name' => 'classification.subgroups.store', 'title' => 'Crear subgrupos.', 'description' => 'Permite crear subgrupos.'],
                    ['name' => 'classification.subgroups.update', 'title' => 'Actualizar subgrupos.', 'description' => 'Permite actualizar subgrupos.'],
                    ['name' => 'classification.subgroups.delete', 'title' => 'Eliminar subgrupos.', 'description' => 'Permite eliminar subgrupos.'],
                    ['name' => 'classification.subgroups.restore', 'title' => 'Restaurar subgrupos.', 'description' => 'Permite restaurar subgrupos.'],
                    ['name' => 'classification.subgroups.settings', 'title' => 'Configurar subgrupos.', 'description' => 'Permite configurar subgrupos.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'audits',
                    'title' => 'Auditorías',
                    'description' => 'Gestión de logs de auditorías',
                ],
                'permissions' => [
                    ['name' => 'audits.all', 'title' => 'Listar auditorías.', 'description' => 'Permite listar auditorías.'],
                    ['name' => 'audits.find', 'title' => 'Consultar auditorías.', 'description' => 'Permite consultar auditorías.'],
                    ['name' => 'audits.search', 'title' => 'Buscar auditorías.', 'description' => 'Permite buscar auditorías.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'workflow',
                    'title' => 'Procesos y Subprocesos',
                    'description' => 'Gestión de procesos y subprocesos',
                ],
                'permissions' => [
                    ['name' => 'workflow.processes.all', 'title' => 'Listar procesos.', 'description' => 'Permite listar procesos..'],
                    ['name' => 'workflow.processes.find', 'title' => 'Buscar un proceso.', 'description' => 'Permite obtener un proceso especifico.'],
                    ['name' => 'workflow.processes.store', 'title' => 'Crear procesos.', 'description' => 'Permite crear procesos.'],
                    ['name' => 'workflow.processes.update', 'title' => 'Actualizar procesos.', 'description' => 'Permite actualizar procesos.'],
                    ['name' => 'workflow.processes.delete', 'title' => 'Eliminar procesos.', 'description' => 'Permite eliminar procesos.'],
                    ['name' => 'workflow.processes.restore', 'title' => 'Resturar procesos.', 'description' => 'Permite restaurar procesos.'],
                    ['name' => 'workflow.processes.settings', 'title' => 'Configurar procesos.', 'description' => 'Permite configurar procesos.'],
                    ['name' => 'workflow.processes.subprocesses.all', 'title' => 'Listar subprocesos.', 'description' => 'Permite listar subprocesos..'],
                    ['name' => 'workflow.processes.subprocesses.find', 'title' => 'Buscar un subproceso.', 'description' => 'Permite obtener un subproceso especifico.'],
                    ['name' => 'workflow.processes.subprocesses.store', 'title' => 'Crear subprocesos.', 'description' => 'Permite crear subprocesos.'],
                    ['name' => 'workflow.processes.subprocesses.update', 'title' => 'Actualizar subprocesos.', 'description' => 'Permite actualizar subprocesos.'],
                    ['name' => 'workflow.processes.subprocesses.delete', 'title' => 'Eliminar subprocesos.', 'description' => 'Permite eliminar subprocesos.'],
                    ['name' => 'workflow.processes.subprocesses.restore', 'title' => 'Resturar subprocesos.', 'description' => 'Permite restaurar subprocesos.'],
                    ['name' => 'workflow.processes.subprocesses.settings', 'title' => 'Configurar subprocesos.', 'description' => 'Permite configurar subprocesos.'],
                    ['name' => 'workflow.processes.subprocesses.operations.all', 'title' => 'Listar operaciones.', 'description' => 'Permite listar operaciones.'],
                    ['name' => 'workflow.processes.subprocesses.operations.find', 'title' => 'Buscar una operacion.', 'description' => 'Permite obtener una operación especifico.'],
                    ['name' => 'workflow.processes.subprocesses.operations.store', 'title' => 'Crear operaciones.', 'description' => 'Permite crear operaciones.'],
                    ['name' => 'workflow.processes.subprocesses.operations.update', 'title' => 'Actualizar operaciones.', 'description' => 'Permite actualizar operaciones.'],
                    ['name' => 'workflow.processes.subprocesses.operations.delete', 'title' => 'Eliminar operaciones.', 'description' => 'Permite eliminar operaciones.'],
                    ['name' => 'workflow.processes.subprocesses.operations.restore', 'title' => 'Resturar operaciones.', 'description' => 'Permite restaurar operaciones.'],
                    ['name' => 'workflow.processes.subprocesses.operations.settings', 'title' => 'Configurar operaciones.', 'description' => 'Permite configurar operaciones.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'wash_tones',
                    'title' => 'Tonos de lavado',
                    'description' => 'Gestión de tonos de lavado.',
                ],
                'permissions' => [
                    ['name' => 'wash_tones.all', 'title' => 'Listar tonos de lavado', 'description' => 'Permite listar tonos de lavado.'],
                    ['name' => 'wash_tones.find', 'title' => 'Consultar tono de lavado', 'description' => 'Permite consultar tono de lavado.'],
                    ['name' => 'wash_tones.store', 'title' => 'Crear tono de lavado', 'description' => 'Permite crear tonos de lavado.'],
                    ['name' => 'wash_tones.update', 'title' => 'Actualizar tono de lavado', 'description' => 'Permite actualizar tonos de lavado.'],
                    ['name' => 'wash_tones.delete', 'title' => 'Eliminar tono de lavado', 'description' => 'Permite eliminar tonos de lavado.'],
                    ['name' => 'wash_tones.restore', 'title' => 'Restaurar tono de lavado', 'description' => 'Permite restaurar tonos de lavado.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'colors',
                    'title' => 'Colores',
                    'description' => 'Gestión de colores.',
                ],
                'permissions' => [
                    ['name' => 'colors.all', 'title' => 'Listar colores', 'description' => 'Permite listar colores.'],
                    ['name' => 'colors.find', 'title' => 'Consultar color', 'description' => 'Permite consultar color.'],
                    ['name' => 'colors.store', 'title' => 'Crear color', 'description' => 'Permite crear colores.'],
                    ['name' => 'colors.update', 'title' => 'Actualizar color', 'description' => 'Permite actualizar colores.'],
                    ['name' => 'colors.delete', 'title' => 'Eliminar color', 'description' => 'Permite eliminar colores.'],
                    ['name' => 'colors.restore', 'title' => 'Restaurar color', 'description' => 'Permite restaurar colores.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'collections',
                    'title' => 'Colecciones',
                    'description' => 'Gestión de colecciones.',
                ],
                'permissions' => [
                    ['name' => 'collections.all', 'title' => 'Listar colecciones', 'description' => 'Permite listar colecciones.'],
                    ['name' => 'collections.find', 'title' => 'Consultar colección', 'description' => 'Permite consultar colección.'],
                    ['name' => 'collections.store', 'title' => 'Crear colección', 'description' => 'Permite crear colecciones.'],
                    ['name' => 'collections.update', 'title' => 'Actualizar colección', 'description' => 'Permite actualizar colecciones.'],
                    ['name' => 'collections.delete', 'title' => 'Eliminar colección', 'description' => 'Permite eliminar colecciones.'],
                    ['name' => 'collections.restore', 'title' => 'Restaurar colección', 'description' => 'Permite restaurar colecciones.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'yoke_types',
                    'title' => 'Tipos de cotilla',
                    'description' => 'Gestión de tipos de cotilla.',
                ],
                'permissions' => [
                    ['name' => 'yoke_types.all', 'title' => 'Listar tipos de cotilla', 'description' => 'Permite listar tipos de cotilla.'],
                    ['name' => 'yoke_types.find', 'title' => 'Consultar tipo de cotilla', 'description' => 'Permite consultar tipo de cotilla.'],
                    ['name' => 'yoke_types.store', 'title' => 'Crear tipo de cotilla', 'description' => 'Permite crear tipos de cotilla.'],
                    ['name' => 'yoke_types.update', 'title' => 'Actualizar tipo de cotilla', 'description' => 'Permite actualizar tipos de cotilla.'],
                    ['name' => 'yoke_types.delete', 'title' => 'Eliminar tipo de cotilla', 'description' => 'Permite eliminar tipos de cotilla.'],
                    ['name' => 'yoke_types.restore', 'title' => 'Restaurar tipo de cotilla', 'description' => 'Permite restaurar tipos de cotilla.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'suppliers',
                    'title' => 'Proveedores',
                    'description' => 'Gestión de proveedores.',
                ],
                'permissions' => [
                    ['name' => 'suppliers.all', 'title' => 'Listar proveedores', 'description' => 'Permite listar proveedores.'],
                    ['name' => 'suppliers.find', 'title' => 'Consultar proveedores', 'description' => 'Permite consultar proveedores'],
                    ['name' => 'suppliers.store', 'title' => 'Crear proveedores', 'description' => 'Permite crear proveedores.'],
                    ['name' => 'suppliers.update', 'title' => 'Actualizar proveedores', 'description' => 'Permite actualizar proveedores.'],
                    ['name' => 'suppliers.delete', 'title' => 'Eliminar proveedores', 'description' => 'Permite eliminar proveedores.'],
                    ['name' => 'suppliers.restore', 'title' => 'Restaurar proveedores', 'description' => 'Permite restaurar proveedores.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'fabric_types',
                    'title' => 'Tipos de Tela',
                    'description' => 'Gestión de tipos de tela.',
                ],
                'permissions' => [
                    ['name' => 'fabric_types.all', 'title' => 'Listar tipos de tela', 'description' => 'Permite listar tipos de tela.'],
                    ['name' => 'fabric_types.find', 'title' => 'Consultar tipos de tela', 'description' => 'Permite consultar tipos de tela'],
                    ['name' => 'fabric_types.store', 'title' => 'Crear tipos de tela', 'description' => 'Permite crear tipos de tela.'],
                    ['name' => 'fabric_types.update', 'title' => 'Actualizar tipos de tela', 'description' => 'Permite actualizar tipos de tela.'],
                    ['name' => 'fabric_types.delete', 'title' => 'Eliminar tipos de tela', 'description' => 'Permite eliminar tipos de tela.'],
                    ['name' => 'fabric_types.restore', 'title' => 'Restaurar tipos de tela', 'description' => 'Permite restaurar tipos de tela.'],
                    ['name' => 'fabric_types.settings', 'title' => 'Configurar tipos de tela', 'description' => 'Permite configurar tipos de tela.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'thread_types',
                    'title' => 'Tipos de Hilo',
                    'description' => 'Gestión de tipos de hilo.',
                ],
                'permissions' => [
                    ['name' => 'thread_types.all', 'title' => 'Listar tipos de hilo', 'description' => 'Permite listar tipos de hilo.'],
                    ['name' => 'thread_types.find', 'title' => 'Consultar tipos de hilo', 'description' => 'Permite consultar tipos de hilo'],
                    ['name' => 'thread_types.store', 'title' => 'Crear tipos de hilo', 'description' => 'Permite crear tipos de hilo.'],
                    ['name' => 'thread_types.update', 'title' => 'Actualizar tipos de hilo', 'description' => 'Permite actualizar tipos de hilo.'],
                    ['name' => 'thread_types.delete', 'title' => 'Eliminar tipos de hilo', 'description' => 'Permite eliminar tipos de hilo.'],
                    ['name' => 'thread_types.restore', 'title' => 'Restaurar tipos de hilo', 'description' => 'Permite restaurar tipos de hilo.'],
                    ['name' => 'thread_types.settings', 'title' => 'Configurar tipos de hilo', 'description' => 'Permite configurar tipos de hilo.'],
                ]
            ],

            [
                'role' => [
                    'name' => 'typification',
                    'title' => 'Tipificaciones',
                    'description' => 'Gestión de tipos de insumo y variantes.',
                ],
                'permissions' => [
                    ['name' => 'typification.supply_types.all', 'title' => 'Listar tipos de insumo', 'description' => 'Permite listar tipos de insumo.'],
                    ['name' => 'typification.supply_types.find', 'title' => 'Consultar tipos de insumo', 'description' => 'Permite consultar tipos de insumo'],
                    ['name' => 'typification.supply_types.store', 'title' => 'Crear tipos de insumo', 'description' => 'Permite crear tipos de insumo.'],
                    ['name' => 'typification.supply_types.update', 'title' => 'Actualizar tipos de insumo', 'description' => 'Permite actualizar tipos de insumo.'],
                    ['name' => 'typification.supply_types.delete', 'title' => 'Eliminar tipos de insumo', 'description' => 'Permite eliminar tipos de insumo.'],
                    ['name' => 'typification.supply_types.restore', 'title' => 'Restaurar tipos de insumo', 'description' => 'Permite restaurar tipos de insumo.'],
                    ['name' => 'typification.supply_types.settings', 'title' => 'Configurar tipos de insumo', 'description' => 'Permite configurar tipos de insumo.'],
                    ['name' => 'typification.supply_types.variants.all', 'title' => 'Listar variantes', 'description' => 'Permite listar variantes.'],
                    ['name' => 'typification.supply_types.variants.find', 'title' => 'Consultar variantes', 'description' => 'Permite consultar variantes'],
                    ['name' => 'typification.supply_types.variants.store', 'title' => 'Crear variantes', 'description' => 'Permite crear variantes.'],
                    ['name' => 'typification.supply_types.variants.update', 'title' => 'Actualizar variantes', 'description' => 'Permite actualizar variantes.'],
                    ['name' => 'typification.supply_types.variants.delete', 'title' => 'Eliminar variantes', 'description' => 'Permite eliminar variantes.'],
                    ['name' => 'typification.supply_types.variants.restore', 'title' => 'Restaurar variantes', 'description' => 'Permite restaurar variantes.'],
                    ['name' => 'typification.supply_types.variants.settings', 'title' => 'Configurar variantes', 'description' => 'Permite configurar variantes.'],

                ]
            ],
            [
                'role' => [
                    'name' => 'management',
                    'title' => 'Gestión',
                    'description' => 'Gestión.',
                ],
                'permissions' => [
                    ['name' => 'management.collections.find', 'title' => 'Consultar colecciones', 'description' => 'Permite consultar colecciones.'],
                ]
            ],

        ];

        foreach ($roles_and_permissions as $role_and_permission) {

            $role = Role::create([
                'name' => $role_and_permission['role']['name'],
                'title' => $role_and_permission['role']['title'],
                'description' => $role_and_permission['role']['description']
            ]);

            foreach ($role_and_permission['permissions'] as $permission) {
                $permission = Permission::create([
                    'name' => $permission['name'],
                    'title' => $permission['title'],
                    'description' => $permission['description'],
                ]);
                $role->givePermissionTo($permission);
            }
        }
    }
}

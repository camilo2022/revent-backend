<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\RiskManager;
use App\Models\BloodType;
use App\Models\CompensationFund;
use App\Models\HealthEntity;
use App\Models\FileSubtype;
use App\Models\FileType;
use App\Models\Gender;
use App\Models\Integration;
use App\Models\Item;
use App\Models\PensionFund;
use App\Models\Position;
use Illuminate\Database\Seeder;

class ItemsAndSubitemsSeeder extends Seeder
{
    public function run(): void
    {
        $item = new Item();
        $item->name = 'Integraciones';
        $item->description = 'Administración de credenciales, endpoints, parámetros y configuraciones necesarias para la integración con plataformas y servicios externos.';
        $item->save();

        $integration = new Integration();
        $integration->name = 'Siigo';
        $integration->description = 'Integración API Siigo.';
        $integration->settings = [
            'default_environment' => 'production',
            'environments' => [
                'production' => [
                    'base_url' => 'https://api.siigo.com',
                    'credentials' => [
                        'username' => 'reventgestion@gmail.com',
                        'access_key' => 'NWIwZTQ3ZmUtZjg0ZS00YzU0LWJlZjYtNzliMGIyOWIxMzk2Oj0/aTw2UDlxWFo='
                    ]
                ],
            ],
            'auth' => [
                'endpoint' => '/auth',
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Partner-Id' => 'consultadeFacturas'
                ],
                'body' => [
                    'username' => [
                        'attribute' => 'Usuario',
                        'rules' => ['required', 'string']
                    ],
                    'access_key' => [
                        'attribute' => 'Llave de acceso',
                        'rules' => ['required', 'string']
                    ]
                ],
                'response' => 'access_token'
            ],
            'endpoints' => [
                'sales_invoice' => [
                    'list_invoices' => [
                        'method' => 'GET',
                        'uri' => '/v1/invoices',
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => ':access_token',
                            'Partner-Id' => 'consultadeFacturas'
                        ],
                        'parameters' => [
                            'created_start' => [
                                'attribute' => 'Fecha creación inicial',
                                'rules' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/']
                            ],
                            'created_end' => [
                                'attribute' => 'Fecha creación final',
                                'rules' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/', 'after:created_start']
                            ],
                            'updated_start' => [
                                'attribute' => 'Fecha actualización inicial',
                                'rules' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/']
                            ],
                            'updated_end' => [
                                'attribute' => 'Fecha actualización final',
                                'rules' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/', 'after:updated_start']
                            ],
                            'name' => [
                                'attribute' => 'Nombre de la factura',
                                'rules' => ['nullable', 'string', 'max:255']
                            ],
                            'customer_identification' => [
                                'attribute' => 'Identificación del cliente',
                                'rules' => ['nullable', 'string', 'max:255']
                            ],
                            'customer_branch_office' => [
                                'attribute' => 'Sucursal del cliente',
                                'rules' => ['nullable', 'string', 'max:255']
                            ],
                            'document_id' => [
                                'attribute' => 'Documento',
                                'rules' => ['nullable', 'integer', 'min:1']
                            ],
                            'date_start' => [
                                'attribute' => 'Fecha elaboración inicial',
                                'rules' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/']
                            ],
                            'date_end' => [
                                'attribute' => 'Fecha elaboración final',
                                'rules' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/', 'after:date_start']
                            ],
                            'page' => [
                                'attribute' => 'Página',
                                'rules' => ['nullable', 'integer', 'min:1']
                            ],
                            'page_size' => [
                                'attribute' => 'Cantidad por página',
                                'rules' => ['nullable', 'integer', 'min:1']
                            ]
                        ],
                        'response' => [
                            'data' => 'results',
                            'pagination' => [
                                'page' => 'pagination.page',
                                'page_size' => 'pagination.page_size',
                                'total_results' => 'pagination.total_results',
                            ],
                            'links' => [
                                'previous' => '_links.previous.href',
                                'self' => '_links.self.href',
                                'next' => '_links.next.href',
                            ]
                        ],
                    ]
                ],
                'products' => [
                    'list_products' => [
                        'method' => 'GET',
                        'uri' => '/v1/products',
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => ':access_token',
                            'Partner-Id' => 'consultadeFacturas'
                        ],
                        'parameters' => [
                            'code' => [
                                'attribute' => 'Código del producto',
                                'rules' => ['nullable', 'string', 'max:255']
                            ],
                            'created_start' => [
                                'attribute' => 'Fecha creación inicial',
                                'rules' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/']
                            ],
                            'created_end' => [
                                'attribute' => 'Fecha creación final',
                                'rules' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/', 'after:created_start']
                            ],
                            'updated_start' => [
                                'attribute' => 'Fecha actualización inicial',
                                'rules' => ['nullable', 'date','regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/']
                            ],
                            'updated_end' => [
                                'attribute' => 'Fecha actualización final',
                                'rules' => ['nullable', 'date', 'regex:/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/', 'after:updated_start']
                            ],
                            'page' => [
                                'attribute' => 'Página',
                                'rules' => ['nullable', 'integer', 'min:1']
                            ],
                            'page_size' => [
                                'attribute' => 'Cantidad por página',
                                'rules' => ['nullable', 'integer', 'min:1']
                            ]
                        ],
                        'response' => [
                            'data' => 'results',
                            'pagination' => [
                                'page' => 'pagination.page',
                                'page_size' => 'pagination.page_size',
                                'total_results' => 'pagination.total_results',
                            ],
                            'links' => [
                                'previous' => '_links.previous.href',
                                'self' => '_links.self.href',
                                'next' => '_links.next.href',
                            ]
                        ],
                    ]
                ]
            ]
        ];
        $integration->save();

        $item = new Item();
        $item->name = 'Tipos de archivos';
        $item->description = 'Clasificación estandarizada de archivos según su naturaleza, uso y propósito dentro de procesos administrativos, legales o documentales.';
        $item->save();

        $file_type = new FileType();
        $file_type->name = 'No aplica';
        $file_type->description = 'No aplica';
        $file_type->save();

        $item = new Item();
        $item->name = 'Subtipos de archivos';
        $item->description = 'Clasificación específica de archivos según su contenido y función.';
        $item->save();

        $file_subtype = new FileSubtype();
        $file_subtype->name = 'No aplica';
        $file_subtype->description = 'No aplica';
        $file_subtype->save();

        $file_subtype->file_types()->sync([$file_type->id]);

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
    }
}

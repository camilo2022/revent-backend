<?php

/**
 * Configuración central de la integración con Siigo.
 *
 * Todo valor que cambie por ambiente (credenciales, URLs, correos) se lee del
 * .env a través de este archivo. En el código se consulta con config('siigo.*');
 * nunca se escribe una URL, credencial o correo directamente en un controlador.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Credenciales de la API pública (api.siigo.com)
    |--------------------------------------------------------------------------
    */
    'username' => env('SIIGO_USERNAME'),
    'access_key' => env('SIIGO_ACCESS_KEY'),
    'partner_id' => env('SIIGO_PARTNER_ID', 'consultadeFacturas'),

    /*
    |--------------------------------------------------------------------------
    | Hosts
    |--------------------------------------------------------------------------
    | api       : API pública (REST v1)
    | services  : microservicios internos de Siigo Nube
    | nube      : front de Siigo Nube (solo para armar enlaces)
    | monolith  : ERP de la empresa (ASPX), incluye el nombre de la empresa
    */
    'urls' => [
        'api' => rtrim(env('SIIGO_BASE_URL', 'https://api.siigo.com'), '/'),
        'services' => rtrim(env('SIIGO_SERVICES_URL', 'https://services.siigo.com'), '/'),
        'nube' => rtrim(env('SIIGO_NUBE_URL', 'https://siigonube.siigo.com'), '/'),
        'monolith' => rtrim(env('SIIGO_MONOLITH_URL', 'https://monolithprod.siigo.com/REVENTCALZADOSAS'), '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoints (relativos a su host)
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'api' => [
            'auth' => '/auth',
            'warehouses' => '/v1/warehouses',
            'products' => '/v1/products',
            'purchases' => '/v1/purchases',
            'invoices' => '/v1/invoices',
            'credit_notes' => '/v1/credit-notes',
            'users' => '/v1/users',
            'cost_centers' => '/v1/cost-centers',
        ],
        'services' => [
            'report' => '/document/api/v1/reports/getreport',
            'autocomplete' => '/catalog/api/v1/Autocomplete/GetData',
            'catalog' => '/catalog/api',
            'quicksearch' => '/cross/api/v2/quicksearch',
            'load_settings' => '/cross/globalstate/api/v1/Settings/LoadSettings',
            'purchase_item' => '/ACEntryApi/api/v2/Purchase/GetItem',
            'acreport' => '/ACReportApi/api/v1/Report/post',
        ],
        'monolith' => [
            'erp_doc_handler' => '/Components/ERP/Business/ERPDocHandler.ashx',
            'default' => '/Default.aspx',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enlaces a documentos en Siigo Nube (relativos a urls.nube)
    | {id} se reemplaza con SiigoLinks::*
    |--------------------------------------------------------------------------
    */
    'links' => [
        'payment' => '/#/paymentsv2/1016/view/{id}',
        'purchase_invoice' => '/#/purchase/1008/{id}',
        'sales_invoice' => '/#/invoice/843/{id}',
        'transfer_note' => '/#/inventories/1372/{id}',
        'inventory_entry' => '/#/inventories/1542/{id}',
        'credit_note' => '/#/credit-note/1017/{id}',
        'purchase_order_tab' => 1671,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tiempos de espera y reintentos de las llamadas HTTP
    |--------------------------------------------------------------------------
    */
    'http' => [
        'connect_timeout' => 30,
        'timeout' => 600,
        'retries' => 5,
        'retry_sleep_ms' => 10000,
        'page_pause_us' => 500000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token de la API pública
    |--------------------------------------------------------------------------
    | Se guarda en caché para no autenticar en cada petición.
    | ttl en minutos; el token de Siigo dura 24 h.
    */
    'token' => [
        'cache_key' => 'siigo.api_token',
        'ttl' => (int) env('SIIGO_TOKEN_TTL', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Archivos de exporte
    |--------------------------------------------------------------------------
    | Los Excel se envían adjuntos al correo. Si pesan más de max_attachment_mb
    | se guardan en el disco público (exports/) con enlace de descarga y
    | siigo:purge-exports los borra pasados retention_days días.
    */
    'exports' => [
        'max_attachment_mb' => (int) env('SIIGO_EXPORT_MAX_ATTACHMENT_MB', 20),
        'retention_days' => (int) env('SIIGO_EXPORT_RETENTION_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Complementos (add-ons) que Siigo exige en algunos reportes
    |--------------------------------------------------------------------------
    */
    'report_addons' => [
        'pos_web' => [
            'name' => 'POS Web',
            'state' => true,
            'tenantId' => env('SIIGO_POS_WEB_TENANT_ID', '0x00000000000000000000000000605286'),
            'type' => 1,
            'module' => 5,
            'complements' => null,
            'dateActive' => '07/06/2026 11:52:42.756',
            'documentBase' => 0,
            'payrollComplements' => null,
            'posActiveCashiers' => [
                'baseCashiers' => 1,
                'aditionalCashiers' => 26,
            ],
            'readOnly' => null,
            'subState' => 1,
            'updateType' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporte de inventarios
    |--------------------------------------------------------------------------
    | excluded_warehouses     : ids de bodega que no salen en el reporte
    | excluded_account_groups : grupos de cuenta (nombre, en mayúsculas) que no salen
    | entry_account_groups    : grupos que se consultan en la trazabilidad para las fechas de ingreso
    | entries_lookback_days   : cuántos días atrás se buscan los ingresos de cada referencia
    */
    'inventory' => [
        'excluded_warehouses' => [34, 35, 36, 44, 45, 47, -1],
        'excluded_account_groups' => ['MATERIA PRIMA', 'SERVICIOS TEMPORAL'],
        'entry_account_groups' => [
            ['id' => 1337, 'name' => 'Compra de Calzados'],
            ['id' => 1189, 'name' => 'Elab. de Calzados '],
        ],
        'entries_lookback_days' => (int) env('SIIGO_INVENTORY_ENTRIES_LOOKBACK_DAYS', 365),
        // Auditoría de tránsitos: días antes de la fecha inicial en que se buscan ingresos al tránsito.
        'transit_lookback_days' => (int) env('SIIGO_TRANSIT_LOOKBACK_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Correos
    |--------------------------------------------------------------------------
    | Listas separadas por coma en el .env; si no existen se usan los valores
    | por defecto de aquí.
    */
    'mail' => [
        'domain' => env('SIIGO_MAIL_DOMAIN', 'revent.com.co'),

        // Destinatarios por defecto de los exportes (facturas, compras, 360).
        'exports' => array_filter(array_map('trim', explode(',', env(
            'SIIGO_MAIL_EXPORTS',
            'operaciones@revent.com.co,ingenieria@revent.com.co,leanmanagement@revent.com.co,tecnologia@revent.com.co'
        )))),

        // Destinatarios por defecto del exporte de inventario.
        'inventory' => array_filter(array_map('trim', explode(',', env(
            'SIIGO_MAIL_INVENTORY',
            'operaciones@revent.com.co,ingenieria@revent.com.co,leanmanagement@revent.com.co,tecnologia@revent.com.co,auditoria@revent.com.co,inventario@revent.com.co'
        )))),

        'accounting' => env('SIIGO_MAIL_ACCOUNTING', 'contabilidad@revent.com.co'),
        'operations' => env('SIIGO_MAIL_OPERATIONS', 'operaciones@revent.com.co'),
        'technology' => env('SIIGO_MAIL_TECHNOLOGY', 'tecnologia@revent.com.co'),
        'purchase_reception' => env('SIIGO_MAIL_PURCHASE_RECEPTION', 'recepciondeoc@revent.com.co'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quién puede pedir enlace de acceso a cada módulo protegido
    |--------------------------------------------------------------------------
    | Para account_payable el valor es el "required_token" que viaja en el enlace.
    */
    'access' => [
        'account_payable' => [
            'contabilidad@revent.com.co' => true,
            'tecnologia@revent.com.co' => true,
            'reventcalzado@gmail.com' => false,
        ],
        'invoice_purchase_order' => array_filter(array_map('trim', explode(',', env(
            'SIIGO_ACCESS_INVOICE_PURCHASE_ORDER',
            'tecnologia@revent.com.co,operaciones@revent.com.co,recepciondeoc@revent.com.co'
        )))),
        'link_ttl_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pausas y reintentos al crear documentos (órdenes de compra, traslados)
    |--------------------------------------------------------------------------
    | Siigo marca como "duplicidad" dos peticiones de creación que llegan casi
    | al mismo tiempo (mismo segundo), aunque sean documentos distintos. Por eso
    | se espera entre documento y documento y, si aun así Siigo responde
    | duplicidad, se espera y se reintenta. NO acortar estos tiempos.
    */
    'retry' => [
        // Segundos de espera después de crear cada documento, antes de crear el siguiente.
        'document_pause_seconds' => 45,
        // Intentos totales ante un falso "duplicidad" y espera entre cada uno.
        'duplicate_max_attempts' => 3,
        'duplicate_wait_seconds' => 45,
        // Lectura de la orden de compra recién creada: intentos y espera (paso * nº de intento).
        'consult_max_attempts' => 3,
        'consult_wait_step_seconds' => 10,
    ],
];

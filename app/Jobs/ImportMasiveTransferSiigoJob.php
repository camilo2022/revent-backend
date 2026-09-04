<?php

namespace App\Jobs;

use App\Exports\MasiveTransferSiigoMultiSheetExport;
use App\Mail\MasiveTransferSiigoMail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportMasiveTransferSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;

    public function __construct(
        private Collection $transfer,
        private string $email
    ) {}

    public function handle(): void
    {
        $traslados = [];
        $errors = [];

        $config = $this->transfer['traslado'][0]->toArray();

        $token = $config['token'] ?? null;

        if (empty($token)) {
            $errors[] = [
                'Row' => 'TOKEN',
                'Error' => 'El token es obligatorio',
            ];
        }

        if (isset($config['fecha']) && $config['fecha'] != '') {
            $fecha = $config['fecha'];
            if (is_numeric($fecha)) {
                $config['fecha'] = Date::excelToDateTimeObject($fecha)->format('Y-m-d');
            } else {
                $config['fecha'] = Carbon::parse($fecha)->format('Y-m-d');
            }

            if (Carbon::parse($config['fecha'])->gt(Carbon::today())) {
                $errors[] = [
                    'Row' => 'FECHA',
                    'Error' => 'La fecha no puede ser mayor a la fecha actual',
                ];
            }
        } else {
            $errors[] = [
                'Row' => 'FECHA',
                'Error' => 'La fecha es obligatoria',
            ];
        }

        if ($config['validar_disponible'] != 'SI' && $config['validar_disponible'] != 'NO') {
            $errors[] = [
                'Row' => 'VALIDAR DISPONIBLE',
                'Error' => 'El campo validar disponible debe ser SI o NO',
            ];
        }

        if ($config['tipo'] != 'DIRECTO' && $config['tipo'] != 'TRANSITO') {
            $errors[] = [
                'Row' => 'TIPO',
                'Error' => 'El tipo de traslado debe ser DIRECTO o TRANSITO',
            ];
        }

        if (!empty($errors)) {
            $this->notificarResultado($traslados, $errors);
            return;
        }

        [$user, $validate] = $this->obtener_datos_usuario($token);
        $errors = array_merge($errors, $validate);

        if (!empty($errors)) {
            $this->notificarResultado($traslados, $errors);
            return;
        }

        $bodegas = $this->bodegas();

        $traslado_detalles = $this->agrupar_duplicados($this->transfer['traslado_detalles']->toArray(), $bodegas);

        [$traslado_detalles, $validate] = $this->validar_permiso_bodega($traslado_detalles, $bodegas, $user);
        $errors = array_merge($errors, $validate);

        [$detalles, $validate] = $this->buscar_producto($token, $traslado_detalles);
        $errors = array_merge($errors, $validate);

        $validate = $this->validar_bodega_salida($token, $detalles, $config);
        $errors = array_merge($errors, $validate);

        $validate = $this->validar_bodega_entrada($token, $detalles, $config);
        $errors = array_merge($errors, $validate);

        if ($config['tipo'] == 'TRANSITO') {
            [$detalles, $validate] = $this->validar_bodega_transito($detalles, $bodegas);
            $errors = array_merge($errors, $validate);
        }

        $detalles = $this->separar_traslados($detalles);

        if (empty($errors)) {

            foreach ($detalles as $detalle) {
                [$traslado, $validate] = $this->traslado($token, $detalle, $config, $bodegas);
                $errors = array_merge($errors, $validate);
                $traslados[] = $traslado;

                if ($traslado['data']['bodega_salida_data'] && !empty($traslado['data']['bodega_salida_data']['emails'] ?? [])) {
                    $emails = $traslado['data']['bodega_salida_data']['emails'] ?? [];

                    if (!empty($emails)) {
                        Mail::to($emails)->send(new MasiveTransferSiigoMail(traslados: $traslados, template_view: 'email.masive-transfer-exit-siigo'));
                    }
                }

                if ($traslado['data']['bodega_ingreso_data'] && !empty($traslado['data']['bodega_ingreso_data']['emails'] ?? [])) {
                    $emails = $traslado['data']['bodega_ingreso_data']['emails'] ?? [];

                    if (!empty($emails)) {
                        Mail::to($emails)->send(new MasiveTransferSiigoMail(traslados: $traslados, template_view: 'email.masive-transfer-entrance-siigo'));
                    }
                }
            }
        }

        $this->notificarResultado($traslados, $errors);
    }

    private function notificarResultado(array $traslados, array $errors): void
    {
        Mail::to([$this->email])->send(new MasiveTransferSiigoMail(traslados: $traslados, errors: $errors));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportMasiveTransferSiigoJob falló', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);

        Mail::to([$this->email])->send(new MasiveTransferSiigoMail(
            traslados: [],
            errors: [
                [
                    'Row'   => 'ERROR DESCONOCIDO',
                    'Error' => 'Ocurrió un error inesperado procesando el traslado masivo: ' . $exception->getMessage(),
                ],
            ]
        ));
    }

    private function agrupar_duplicados(array $detalles): array
    {
        return collect($detalles)
            ->groupBy(function ($item) {
                return $item['codigo'] . '|' .
                    $item['bodega_salida'] . '|' .
                    $item['bodega_entrada'];
            })
            ->map(function ($items) {
                $detalle = $items->first();
                $detalle['cantidad'] = $items->sum('cantidad');

                return $detalle;
            })
            ->values()
            ->all();
    }

    private function validar_permiso_bodega(array $detalles, array $bodegas, array $user)
    {
        $validate = [];

        foreach ($detalles as $row => $detalle) {
            $bodega = $bodegas['DIRECTO'][$detalle['bodega_salida']] ?? ($bodegas['TRANSITO'][$detalle['bodega_salida']] ?? null);

            if (empty($bodega)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['codigo'],
                    'Description' => $detalle['codigo'],
                    'WarehouseCode' => $detalle['bodega_salida'] . ' - #N/A',
                    'Error' => 'No tiene acceso a la bodega de salida',
                ];
            } else if (!in_array($user['id'], $bodega['users'] ?? [])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['codigo'],
                    'Description' => $detalle['codigo'],
                    'WarehouseCode' => $detalle['bodega_salida'] . ' - ' . $bodega['name'],
                    'Error' => 'No tiene acceso a la bodega de salida',
                ];
            }
        }

        return [collect($detalles), $validate];
    }

    private function buscar_producto(string $token, Collection $traslados)
    {
        $items = [];
        $validate = [];
        $productosCache = [];

        foreach ($traslados as $row => $traslado) {

            $codigo = $traslado['codigo'];

            if (empty($codigo)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $codigo,
                    'Error' => 'El codigo es requerido',
                ];
                continue;
            }

            if (array_key_exists($codigo, $productosCache)) {
                $producto = $productosCache[$codigo];
            } else {
                $producto = $this->buscar_con_paginacion($token, $codigo, $validate);

                if ($producto === false) {
                    return [[], $validate];
                }

                $productosCache[$codigo] = $producto;
            }

            if (!$producto) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $codigo,
                    'Error' => 'El producto no existe o no tiene control de inventario',
                ];
                continue;
            }

            $items[] = [
                "ProductCode" => $producto['ProductID'],
                "Code" => $producto['Code'],
                "Description" => $producto['Description'],
                "LongDescription" => $producto['Description'],
                "ProductSubType" => $producto['ProductType'],
                "ProductUnitMeasurement" => $producto['MeasurementUnit'],
                "EntryItemType" => $producto['ProductType'],
                "Order" => 1,
                "CostCenterCode" => null,
                "WarehouseCode" => $traslado['bodega_salida'],
                "DestinationWarehouseCode" => $traslado['bodega_entrada'],
                "AccountCode" => 0,
                "SalesmanCode" => null,
                "ConceptCode" => null,
                "Quantity" => $traslado['cantidad'],
                "Value" => 0
            ];
        }

        return [$items, $validate];
    }

    private function buscar_con_paginacion(string $token, string $codigo, array &$validate)
    {
        $numRecordView = 0;
        $maxIntentos = 3;
        $intentos = 0;

        do {
            $body = [
                'type' => 1,
                'browserID' => '33',
                'query' => $codigo,
                'filter' => 'IsInventoryControl=1',
                'numRecordView' => $numRecordView,
                'tags' => (object) []
            ];

            $response = Http::retry(3, 3000)->withToken($token)
                ->asJson()
                ->post('https://services.siigo.com/catalog/api/v1/Autocomplete/GetData', $body);

            if (!$response->successful()) {
                $validate = [
                    [
                        'Row'   => 'ERROR DESCONOCIDO',
                        'Error' => $response->body(),
                    ]
                ];
                return false;
            }

            $rawBody = trim($response->body());

            if ($rawBody === '""' || $rawBody === '' || $rawBody === 'null') {
                return null;
            }

            $data = $response->json();
            $data = json_decode($data, true);

            if (empty($data)) {
                return null;
            }

            $producto = collect($data)->where('Code', $codigo)->first();

            if ($producto) {
                return $producto;
            }

            $numRecordView += 10;
            $intentos++;

        } while ($intentos < $maxIntentos);

        return null;
    }

    private function validar_bodega_salida(string $token, array $detalles, array $config)
    {
        $validate = [];

        $fecha = Carbon::parse($config['fecha'])->format('Ymd');

        foreach ($detalles as $row => $detalle) {
            $productCode = $detalle['ProductCode'];
            $warehouseCode = $detalle['WarehouseCode'];

            $body = [
                'type' => 1,
                'browserID' => '67',
                'query' => '',
                'filter' => "productcode = {$productCode} AND period < CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'))",
                'filter2' => "transactiondate >= CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'), '01') AND CONVERT(DATE, transactiondate, 120) <= CONVERT(DATE, '$fecha', 120) AND productcode = {$productCode}",
                'numRecordView' => 0,
                'tags' => (object) [
                    '{FilterWareHouse}' => "{condition} ProductWarehouseId = {$warehouseCode}",
                ],
            ];

            $response = Http::retry(3, 3000)->withToken($token)
                ->asJson()
                ->post('https://services.siigo.com/catalog/api/v1/Autocomplete/GetData', $body);

            if (!$response->successful()) {
                $validate = [
                    [
                        'Row'   => 'ERROR DESCONOCIDO',
                        'Error' => $response->body(),
                    ]
                ];
                return $validate;
            }

            $data = $response->json();
            $bodegas = !empty($data) ? json_decode($data, true) : [];

            if (empty($bodegas)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $detalle['WarehouseCode'],
                    'Error' => 'La bodega de salida no existe',
                ];
                continue;
            }

            $existencia = collect($bodegas)->where('WarehouseID', $detalle['WarehouseCode'])->first();

            if (!$existencia) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $detalle['WarehouseCode'],
                    'Error' => 'La bodega de salida no existe',
                ];
            } else if ($existencia['Value'] < $detalle['Quantity'] && $config['validar_disponible'] == 'SI') {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $detalle['WarehouseCode'] . ' - ' . $existencia['Warehouse'],
                    'AvailableQuantity' => $existencia['Value'],
                    'RequiredQuantity' => $detalle['Quantity'],
                    'Error' => 'Cantidad insuficiente',
                ];
            }
        }

        return $validate;
    }

    private function validar_bodega_entrada(string $token, array $detalles, array $config)
    {
        $validate = [];

        $fecha = Carbon::parse($config['fecha'])->format('Ymd');

        foreach ($detalles as $row => $detalle) {
            $productCode = $detalle['ProductCode'];
            $warehouseCode = $detalle['DestinationWarehouseCode'];

            $body = [
                'type' => 1,
                'browserID' => '67',
                'query' => '',
                'filter' => "productcode = {$productCode} AND period < CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'))",
                'filter2' => "transactiondate >= CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'), '01') AND CONVERT(DATE, transactiondate, 120) <= CONVERT(DATE, '$fecha', 120) AND productcode = {$productCode}",
                'numRecordView' => 0,
                'tags' => (object) [
                    '{FilterWareHouse}' => "{condition} ProductWarehouseId = {$warehouseCode}",
                ],
            ];

            $response = Http::retry(3, 3000)->withToken($token)
                ->asJson()
                ->post('https://services.siigo.com/catalog/api/v1/Autocomplete/GetData', $body);

            if (!$response->successful()) {
                $validate = [
                    [
                        'Row'   => 'ERROR DESCONOCIDO',
                        'Error' => $response->body(),
                    ]
                ];
                return $validate;
            }

            $data = $response->json();
            $bodegas = !empty($data) ? json_decode($data, true) : [];

            if (empty($bodegas)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $detalle['DestinationWarehouseCode'],
                    'Error' => 'La bodega de entrada no existe',
                ];
                continue;
            }

            $existencia = collect($bodegas)->where('WarehouseID', $detalle['DestinationWarehouseCode'])->first();

            if (!$existencia) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $detalle['DestinationWarehouseCode'],
                    'Error' => 'La bodega de entrada no existe',
                ];
            }
        }

        return $validate;
    }

    private function validar_bodega_transito(array $detalles, array $bodegas)
    {
        $validate = [];

        foreach ($detalles as $row => &$detalle) {
            $bodega = $bodegas['DIRECTO'][$detalle['DestinationWarehouseCode']] ?? null;

            if (!$bodega) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $detalle['DestinationWarehouseCode'] . ' - #N/A',
                    'Error' => 'La bodega de entrada no está configurada para traslado',
                ];
            } else if (!$bodega['transito']) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $detalle['DestinationWarehouseCode'] . ' - ' . $bodega['name'],
                    'Error' => 'La bodega de entrada no tiene bodega de transito configurada',
                ];
            } else if ($bodega['transito'] && !$bodegas['TRANSITO'][$bodega['transito']]) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $bodega['transito'] . ' - #N/A',
                    'Error' => 'La bodega de transito configurada no existe',
                ];
            } else {
                $detalle['DestinationWarehouseCode'] = $bodega['transito'];
            }
        }

        return [$detalles, $validate];
    }

    private function separar_traslados(array $detalles)
    {
        $traslados = [];

        foreach ($detalles as $detalle) {
            $key = $detalle['WarehouseCode'] . '-' . $detalle['DestinationWarehouseCode'];

            if (!isset($traslados[$key])) {
                $traslados[$key] = [];
            }

            $traslados[$key][] = $detalle;
        }

        return array_values($traslados);
    }

    private function traslado(string $token, array $detalles, array $config, array $bodegas)
    {
        $validate = [];

        $fecha = Carbon::parse($config['fecha'])->format('Ymd');
        $observacion = $config['observacion'] ?? '';

        $body = [
            "Items" => $detalles,
            "AttachFiles" => [],
            "EntryType" => (object) [
                "ERPDocumentTypeID" => 23215,
                "Name" => "Nota de traslado entre bodegas",
                "Class" => "NT",
                "Code" => "NT",
                "IsAutomaticEnum" => true,
                "TemplateName" => "TransferVoucher",
                "InternalDescription" => "",
                "ACAccountCode" => -1,
                "UseCostCenter" => false,
                "CostCenterDefault" => null,
                "CostCenterMandatory" => false,
                "AttachmentsFSItemsGUID" => null,
                "AllowDecimals" => false,
                "IsDiscountValue" => false,
                "IsDiscountPercentaje" => false,
                "EmailBody" => null,
                "EmailHeader" => null,
                "EmailCommercialConditions" => null
            ],
            "Entry" => (object) [
                "SalesmanCode" => null,
                "DocName" => "",
                "DocDate" => $fecha,
                "DocNumber" => null,
                "CostCenterCode" => null,
                "ForeignMoneyCode" => null,
                "ExchangeValue" => null,
                "ExchangePersonalized" => null,
                "Observations" => $observacion,
                "AccountCode" => 0,
                "ContactCode" => null,
                "Header" => null,
                "CommercialConditions" => null,
                "ACEntryCode" => -1,
                "ERPDocumentTypeCode" => 23215,
                "IsAllowDecimals" => null,
                "AttachmentsFSItemsGUID" => ""
            ],
            "ModelType" => 23
        ];

        $response = Http::withToken($token)
            ->asJson()
            ->connectTimeout(60)
            ->timeout(300)
            ->post('https://services.siigo.com/ACEntryApi/api/v1/WarehouseTransfer/Save/', $body);

        if (!$response->successful()) {
            $validate = [
                [
                    'Row'   => 'ERROR DESCONOCIDO - TRASLADO',
                    'Error' => $response->body(),
                ]
            ];
            return [[], $validate];
        }

        $data = $response->json();

        $document = $this->consultar_documento($token, $data);

        $bodega_salida_codigo = collect($detalles)->first()['WarehouseCode'];
        $bodega_ingreso_codigo = collect($detalles)->first()['DestinationWarehouseCode'];

        $bodega_salida = $bodegas['DIRECTO'][$bodega_salida_codigo] ?? ($bodegas['TRANSITO'][$bodega_salida_codigo] ?? null);
        $bodega_ingreso = $bodegas['DIRECTO'][$bodega_ingreso_codigo] ?? ($bodegas['TRANSITO'][$bodega_ingreso_codigo] ?? null);

        return [
            [
                'preview' => "https://siigonube.siigo.com/#/inventories/1372/{$data}",
                'preview_download' => "https://siigonube.siigo.com/#/inventories/1372/{$data}/true",
                'approved' => $config['tipo'] == 'TRANSITO' ? $this->generar_excel_aprobar_traslado_transito($document['DocName'], $detalles) : null,
                'data' => [
                    'id' => $data,
                    'document' => $document['DocName'],
                    'user' => $document['CreatedByUser'],
                    'created_at' => $document['CreatedByDate'],
                    'date' => $document['DocDate'],
                    'bodega_salida' => $bodega_salida_codigo,
                    'bodega_ingreso' => $bodega_ingreso_codigo,
                    'bodega_salida_data' => $bodega_salida,
                    'bodega_ingreso_data' => $bodega_ingreso,
                ]
            ],
            $validate
        ];
    }

    private function generar_excel_aprobar_traslado_transito(string $document, array $detalles)
    {
        $bodegas = $this->bodegas();
        $traslado = [
            [
                'token' => '',
                'fecha' => '',
                'validar_disponible' => 'SI',
                'tipo' => 'DIRECTO',
                'observacion' => "APROBACION DE TRASLADO DE TRANSITO: {$document}",
            ]
        ];

        $bodega_salida = "";
        $bodega_entrada = "";

        $traslado_detalles = [];
        foreach ($detalles as $detalle) {
            $bodega_salida = $detalle['DestinationWarehouseCode'];
            $bodega_entrada = collect($bodegas['DIRECTO'])->search(function ($bodega) use ($detalle) {
                return $bodega['transito'] == $detalle['DestinationWarehouseCode'];
            });

            $traslado_detalles[] = [
                'codigo' => $detalle['Code'],
                'descripcion' => $detalle['LongDescription'],
                'bodega_salida' => $bodega_salida,
                'bodega_entrada' => $bodega_entrada,
                'cantidad' => $detalle['Quantity'],
            ];
        }

        $filename = "transfer_{$document}_" . now()->format('Y_m_d_His') . '.xlsx';

        Excel::store(new MasiveTransferSiigoMultiSheetExport($traslado, $traslado_detalles), "exports/{$filename}", 'public');

        $downloadUrl = route('exports.download', ['file' => $filename]);
        $name = $bodega_salida . ' - ' . $bodegas['TRANSITO'][$bodega_salida]['name'] . ' a ' . $bodega_entrada . ' - ' . $bodegas['DIRECTO'][$bodega_entrada]['name'];

        return [
            'name' => $name,
            'download_url' => $downloadUrl,
        ];
    }

    private function bodegas()
    {
        // 597 - ELI "reventcalzado@gmail.com"
        return [
            'DIRECTO' => [
                -1 => ['name' => 'Sin asignar', 'transito' => null, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                2  => ['name' => 'P R I N C I P A L', 'transito' => 67, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                3  => ['name' => 'ALEGRA', 'transito' => 70, 'users' => [597, 735, 742, 816, 823, 824, 873, 875, 878, 879, 880, 883, 884, 957, 972, 975, 979, 997, 1002, 1003, 1049, 1062, 1065, 1068, 1114, 1128, 1163, 1164, 1182, 1223, 1237, 1238, 1242, 1251, 1279, 1280, 1314, 1348, 1350, 1362, 11571, 11579, 11581, 11591], 'emails' => ['parquealegra@revent.com.co']],
                4  => ['name' => 'PUNTO DE VENTA', 'transito' => null, 'users' => [597, 877], 'emails' => ['operaciones@revent.com.co']],
                9  => ['name' => 'MAYALES', 'transito' => 71, 'users' => [597, 948, 951, 952, 978, 1005, 1016, 1059, 1115, 1116, 1126, 1127, 1171, 1175, 1176, 1177, 1179, 1216, 1258, 1291, 1309, 1310, 1311, 1361, 1370, 1372, 1391, 1452, 1471, 1472, 1520, 11578], 'emails' => ['mayales@revent.com.co']],
                13 => ['name' => 'PROD FABRICA', 'transito' => null, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                15 => ['name' => 'REVENT', 'transito' => 68, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                16 => ['name' => 'MATERIA PRIMA', 'transito' => null, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                17 => ['name' => 'OCEAN MALL', 'transito' => 72, 'users' => [597, 1087, 1183, 1197, 1200, 1201, 1224, 1263, 1264, 1390, 1442, 1530, 11572, 11573], 'emails' => ['reventoceanmall@gmail.com']],
                19 => ['name' => 'NUESTRO MONTERIA', 'transito' => 73, 'users' => [597, 982, 983, 984, 985, 987, 989, 994, 1015, 1017, 1026, 1029, 1056, 1057, 1074, 1095, 1096, 1098, 1099, 1104, 1117, 1121, 1135, 1159, 1217, 1219, 1347, 1455, 1470, 11559, 11560, 11602], 'emails' => ['nuestro@revent.com.co']],
                22 => ['name' => 'ALAMEDAS', 'transito' => 74, 'users' => [597, 986, 992, 1034, 1061, 1064, 1092, 1097, 1157, 1162, 1165, 1232, 1288, 1373, 1382, 1408, 1409, 1469, 1533, 11561], 'emails' => ['alamedas@revent.com.co']],
                24 => ['name' => 'PORTAL', 'transito' => 75, 'users' => [597, 732, 792, 865, 871, 887, 888, 891, 893, 894, 896, 897, 898, 899, 938, 959, 971, 998, 1001, 1027, 1063, 1072, 1123, 1131, 1132, 1180, 1188, 1226, 1228, 1278, 1295, 1296, 1315, 1318, 1319, 1331, 1351, 1364, 1365, 1378, 1461, 1462, 1463, 1481, 1497, 1508, 1512, 1523, 11574, 11575, 11576, 11598], 'emails' => ['portaldelprado@revent.com.co']],
                27 => ['name' => 'CARIBE PLAZA 1', 'transito' => null, 'users' => [597], 'emails' => ['caribeplaza@revent.com.co']],
                28 => ['name' => 'PLAZA DEL SOL', 'transito' => 76, 'users' => [597, 780, 930, 1181, 1221, 1261, 1268, 1298, 1328, 1385, 1400, 1430, 1438, 1454, 1467, 1480, 11592], 'emails' => ['plazadelsol@revent.com.co']],
                31 => ['name' => 'WEB', 'transito' => null, 'users' => [597, 940, 980, 1146], 'emails' => ['paginawebrevent@gmail.com']],
                32 => ['name' => 'CASTELLANA', 'transito' => 77, 'users' => [597, 926, 1021, 1035, 1040, 1042, 1044, 1046, 1051, 1052, 1053, 1054, 1055, 1058, 1066, 1070, 1073, 1075, 1082, 1085, 1091, 1102, 1106, 1133, 1134, 1142, 1152, 1153, 1154, 1167, 1173, 1174, 1184, 1185, 1187, 1195, 1196, 1202, 1225, 1227, 1229, 1230, 1265, 1266, 1267, 1275, 1276, 1277, 1299, 1307, 1308, 1317, 1352, 1419, 1444, 1483, 1488, 1492, 1494, 1515, 1516, 1517, 1534, 11569, 11570], 'emails' => ['castellana@revent.com.co']],
                33 => ['name' => 'UNICO BQ', 'transito' => 78, 'users' => [597, 740, 813, 882, 1018, 1122, 1212, 1220, 1222, 1252, 1256, 1259, 1260, 1273, 1274, 1281, 1282, 1283, 1284, 1285, 1286, 1289, 1305, 1312, 1313, 1320, 1321, 1357, 1360, 1375, 1379, 1403, 1424, 1449, 1451, 1460, 1511, 1514, 1524, 11562], 'emails' => ['unico@revent.com.co']],
                34 => ['name' => 'CREAR ENSAMBLES', 'transito' => null, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                35 => ['name' => 'Credito de Calzado', 'transito' => null, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                36 => ['name' => 'ECOMMERCE', 'transito' => null, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                37 => ['name' => 'CARNAVAL', 'transito' => 79, 'users' => [597, 810, 886, 1145, 1147, 1148, 1150, 1151, 1160, 1203, 1233, 1257, 1297, 1324, 1325, 1429, 1466, 1532, 11563, 11565], 'emails' => ['carnaval@revent.com.co']],
                44 => ['name' => 'INSTAGRAM', 'transito' => null, 'users' => [597, 1547]],
                45 => ['name' => 'GARANTIAS', 'transito' => null, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                46 => ['name' => 'GUATAPURI', 'transito' => 80, 'users' => [597, 1037, 1060, 1166, 1170, 1178, 1255, 1271, 1292, 1304, 1329, 1330, 1356, 1384, 1439, 1445, 1473, 1474, 1475, 1504, 11580], 'emails' => ['guatapuri@revent.com.co']],
                47 => ['name' => 'TEMPORADA 2025', 'transito' => null, 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                49 => ['name' => 'VENTURA PLAZA', 'transito' => 81, 'users' => [597, 1344, 1345, 1371, 1383, 1495, 1556, 11556], 'emails' => ['venturaplaza@revent.com.co']],
                50 => ['name' => 'FABRICATO', 'transito' => 82, 'users' => [597, 1332, 1333, 1334, 1335, 1336, 1337, 1338, 1339, 1340, 1341, 1342, 1343, 1346, 1349, 1380, 1387, 1388, 1416, 1418, 1423, 1443, 1468, 1476, 1489, 1490, 1509, 1526, 1527, 1535, 1538, 11557, 11597], 'emails' => ['fabricato@revent.com.co']],
                51 => ['name' => 'UNICO CALI', 'transito' => 83, 'users' => [597, 1205, 1206, 1207, 1209, 1210, 1211, 1213, 1235, 1239, 1240, 1241, 1244, 1245, 1269, 1270, 1300, 1322, 1353, 1359, 1386, 1411, 1412, 1413, 1421, 1433, 1435, 1477, 1496, 1536, 11590], 'emails' => ['unicocali@revent.com.co']],
                56 => ['name' => 'CARIBE PLAZA 2', 'transito' => 84, 'users' => [597], 'emails' => ['caribeplaza@revent.com.co']],
                57 => ['name' => 'MAYORCA', 'transito' => 85, 'users' => [597, 1537, 1542, 1543, 1544, 1545, 1546, 1548, 1549, 1552, 1554, 1555, 11558, 11568, 11577, 11582], 'emails' => ['mayorca@revent.com.co']],
                58 => ['name' => 'GRAN MANZANA', 'transito' => 86, 'users' => [597, 1498, 1500, 1501, 1502, 1503, 1513, 1531, 11584], 'emails' => ['granmanzana@revent.com.co']],
                59 => ['name' => 'NUESTRO ATLANTICO', 'transito' => 87, 'users' => [597, 885, 11585, 11586, 11587, 11588, 11589, 11595], 'emails' => ['nuestroatlantico@revent.com.co']],
                62 => ['name' => 'NUESTRO CARTAGO', 'transito' => 89, 'users' => [597], 'emails' => []],
                63 => ['name' => 'NUESTRO URABÁ', 'transito' => 88, 'users' => [597], 'emails' => []],
                66 => ['name' => 'GUACARI SINCELEJO', 'transito' => 90, 'users' => [597], 'emails' => []],
                69 => ['name' => 'NUESTRO BOGOTÁ', 'transito' => 91, 'users' => [597], 'emails' => []],
            ],
            'TRANSITO' => [
                67 => ['name' => 'TRANSITO P R I N C I P A L', 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                68 => ['name' => 'TRANSITO REVENT', 'users' => [597], 'emails' => ['operaciones@revent.com.co']],
                70 => ['name' => 'TRANSITO ALEGRA', 'users' => [597, 735, 742, 816, 823, 824, 873, 875, 878, 879, 880, 883, 884, 957, 972, 975, 979, 997, 1002, 1003, 1049, 1062, 1065, 1068, 1114, 1128, 1163, 1164, 1182, 1223, 1237, 1238, 1242, 1251, 1279, 1280, 1314, 1348, 1350, 1362, 11571, 11579, 11581, 11591], 'emails' => ['parquealegra@revent.com.co']],
                71 => ['name' => 'TRANSITO MAYALES', 'users' => [597, 948, 951, 952, 978, 1005, 1016, 1059, 1115, 1116, 1126, 1127, 1171, 1175, 1176, 1177, 1179, 1216, 1258, 1291, 1309, 1310, 1311, 1361, 1370, 1372, 1391, 1452, 1471, 1472, 1520, 11578], 'emails' => ['mayales@revent.com.co']],
                72 => ['name' => 'TRANSITO OCEAN MALL', 'users' => [597, 1087, 1183, 1197, 1200, 1201, 1224, 1263, 1264, 1390, 1442, 1530, 11572, 11573], 'emails' => ['reventoceanmall@gmail.com']],
                73 => ['name' => 'TRANSITO NUESTRO MONTERIA', 'users' => [597, 982, 983, 984, 985, 987, 989, 994, 1015, 1017, 1026, 1029, 1056, 1057, 1074, 1095, 1096, 1098, 1099, 1104, 1117, 1121, 1135, 1159, 1217, 1219, 1347, 1455, 1470, 11559, 11560, 11602], 'emails' => ['nuestro@revent.com.co']],
                74 => ['name' => 'TRANSITO ALAMEDAS', 'users' => [597, 986, 992, 1034, 1061, 1064, 1092, 1097, 1157, 1162, 1165, 1232, 1288, 1373, 1382, 1408, 1409, 1469, 1533, 11561], 'emails' => ['alamedas@revent.com.co']],
                75 => ['name' => 'TRANSITO PORTAL', 'users' => [597, 732, 792, 865, 871, 887, 888, 891, 893, 894, 896, 897, 898, 899, 938, 959, 971, 998, 1001, 1027, 1063, 1072, 1123, 1131, 1132, 1180, 1188, 1226, 1228, 1278, 1295, 1296, 1315, 1318, 1319, 1331, 1351, 1364, 1365, 1378, 1461, 1462, 1463, 1481, 1497, 1508, 1512, 1523, 11574, 11575, 11576, 11598], 'emails' => ['portaldelprado@revent.com.co']],
                76 => ['name' => 'TRANSITO PLAZA DEL SOL', 'users' => [597, 780, 930, 1181, 1221, 1261, 1268, 1298, 1328, 1385, 1400, 1430, 1438, 1454, 1467, 1480, 11592], 'emails' => ['plazadelsol@revent.com.co']],
                77 => ['name' => 'TRANSITO CASTELLANA', 'users' => [597, 926, 1021, 1035, 1040, 1042, 1044, 1046, 1051, 1052, 1053, 1054, 1055, 1058, 1066, 1070, 1073, 1075, 1082, 1085, 1091, 1102, 1106, 1133, 1134, 1142, 1152, 1153, 1154, 1167, 1173, 1174, 1184, 1185, 1187, 1195, 1196, 1202, 1225, 1227, 1229, 1230, 1265, 1266, 1267, 1275, 1276, 1277, 1299, 1307, 1308, 1317, 1352, 1419, 1444, 1483, 1488, 1492, 1494, 1515, 1516, 1517, 1534, 11569, 11570], 'emails' => ['castellana@revent.com.co']],
                78 => ['name' => 'TRANSITO UNICO BQ', 'users' => [597, 740, 813, 882, 1018, 1122, 1212, 1220, 1222, 1252, 1256, 1259, 1260, 1273, 1274, 1281, 1282, 1283, 1284, 1285, 1286, 1289, 1305, 1312, 1313, 1320, 1321, 1357, 1360, 1375, 1379, 1403, 1424, 1449, 1451, 1460, 1511, 1514, 1524, 11562], 'emails' => ['unico@revent.com.co']],
                79 => ['name' => 'TRANSITO CARNAVAL', 'users' => [597, 810, 886, 1145, 1147, 1148, 1150, 1151, 1160, 1203, 1233, 1257, 1297, 1324, 1325, 1429, 1466, 1532, 11563, 11565], 'emails' => ['carnaval@revent.com.co']],
                80 => ['name' => 'TRANSITO GUATAPURI', 'users' => [597, 1037, 1060, 1166, 1170, 1178, 1255, 1271, 1292, 1304, 1329, 1330, 1356, 1384, 1439, 1445, 1473, 1474, 1475, 1504, 11580], 'emails' => ['guatapuri@revent.com.co']],
                81 => ['name' => 'TRANSITO VENTURA PLAZA', 'users' => [597, 1344, 1345, 1371, 1383, 1495, 1556, 11556], 'emails' => ['venturaplaza@revent.com.co']],
                82 => ['name' => 'TRANSITO FABRICATO', 'users' => [597, 1332, 1333, 1334, 1335, 1336, 1337, 1338, 1339, 1340, 1341, 1342, 1343, 1346, 1349, 1380, 1387, 1388, 1416, 1418, 1423, 1443, 1468, 1476, 1489, 1490, 1509, 1526, 1527, 1535, 1538, 11557, 11597], 'emails' => ['fabricato@revent.com.co']],
                83 => ['name' => 'TRANSITO UNICO CALI', 'users' => [597, 1205, 1206, 1207, 1209, 1210, 1211, 1213, 1235, 1239, 1240, 1241, 1244, 1245, 1269, 1270, 1300, 1322, 1353, 1359, 1386, 1411, 1412, 1413, 1421, 1433, 1435, 1477, 1496, 1536, 11590], 'emails' => ['unicocali@revent.com.co']],
                84 => ['name' => 'TRANSITO CARIBE PLAZA 2', 'users' => [597], 'emails' => ['caribeplaza@revent.com.co']],
                85 => ['name' => 'TRANSITO MAYORCA', 'users' => [597, 1537, 1542, 1543, 1544, 1545, 1546, 1548, 1549, 1552, 1554, 1555, 11558, 11568, 11577, 11582], 'emails' => ['mayorca@revent.com.co']],
                86 => ['name' => 'TRANSITO GRAN MANZANA', 'users' => [597, 1498, 1500, 1501, 1502, 1503, 1513, 1531, 11584], 'emails' => ['granmanzana@revent.com.co']],
                87 => ['name' => 'TRANSITO NUESTRO ATLANTICO', 'users' => [597, 885, 11585, 11586, 11587, 11588, 11589, 11595], 'emails' => ['nuestroatlantico@revent.com.co']],
                88 => ['name' => 'TRANSITO NUESTRO URABÁ', 'users' => [597], 'emails' => []],
                89 => ['name' => 'TRANSITO NUESTRO CARTAGO', 'users' => [597], 'emails' => []],
                90 => ['name' => 'TRANSITO GUACARI SINCELEJO', 'users' => [597], 'emails' => []],
                91 => ['name' => 'TRANSITO NUESTRO BOGOTÁ', 'users' => [597], 'emails' => []],
            ]
        ];
    }

    private function consultar_documento(string $token, string|int $id)
    {
        $response = Http::retry(3, 3000)->withToken($token)
            ->asJson()
            ->acceptJson()
            ->get('https://services.siigo.com/ACEntryApi/api/v1/WarehouseTransfer/GetDataView', [
                'id' => $id
            ]);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    private function obtener_datos_usuario(string $token)
    {
        $validate = [];
        $response = Http::retry(3, 3000)->withHeaders([
            'Authorization' => $token,
            'Accept' => '*/*',
            'Referer' => 'https://siigonube.siigo.com/',
        ])->get('https://services.siigo.com/cross/globalstate/api/v1/Settings/LoadSettings');

        if (!$response->successful()) {
            $validate = [
                [
                    'Row'   => 'TOKEN',
                    'Error' => 'El token no sirve o expiró, genera uno nuevo',
                ]
            ];

            return [[], $validate];
        }

        $data = $response->json();

        return [
            [
                'id' => $data['userID'],
                'name' => $data['userName'],
            ],
            $validate
        ];
    }
}

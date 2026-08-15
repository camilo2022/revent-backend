<?php

namespace App\Http\Controllers\Integration;

use App\Exports\MasiveTransferSiigoMultiSheetExport;
use App\Http\Controllers\Controller;
use App\Imports\MasiveTransferSiigoSheetsImport;
use App\Services\SiigoInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MasiveTransferSiigoController extends Controller
{
    public function masive_transfer()
    {
        return view('integration.masive_transfer');
    }

    public function masive_transfer_load(Request $request)
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $errors = [];

        $transfer = Excel::toCollection(new MasiveTransferSiigoSheetsImport, $request->file('file'));

        $config = $transfer['traslado'][0]->toArray();
        if (isset($config['fecha']) && $config['fecha'] != '') {
            $fecha = $config['fecha'];
            if (is_numeric($fecha)) {
                $config['fecha'] = Date::excelToDateTimeObject($fecha)->format('Y-m-d');
            } else {
                $config['fecha'] = Carbon::parse($fecha)->format('Y-m-d');
            }
        } else {
            $errors[] = [
                'Row' => 'Configuración',
                'Error' => 'La fecha es obligatoria',
            ];
        }

        if($config['validar_disponible'] != 'SI' && $config['validar_disponible'] != 'NO') {
            $errors[] = [
                'Row' => 'Configuración',
                'Error' => 'El campo validar disponible debe ser SI o NO',
            ];
        }

        if($config['tipo'] != 'DIRECTO' && $config['tipo'] != 'TRANSITO') {
            $errors[] = [
                'Row' => 'Configuración',
                'Error' => 'El tipo de traslado debe ser DIRECTO o TRANSITO',
            ];
        }

        if($config['separar_traslados'] != 'SI' && $config['separar_traslados'] != 'NO') {
            $errors[] = [
                'Row' => 'Configuración',
                'Error' => 'El campo separar traslados debe ser SI o NO',
            ];
        }

        if(!empty($errors)) {

            $contenido = $this->generar_archivo_errores($errors);

            return response($contenido)
                ->header('Content-Type', 'text/plain; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="errores_traslado.txt"');
        }

        $traslados = collect($transfer['traslado_detalles']);

        $bodegas = $this->bodegas();

        [$detalles, $validate] = $this->buscar_producto($token, $traslados);
        $errors = array_merge($errors, $validate);

        $validate = $this->validar_bodega_salida($token, $detalles, $config);
        $errors = array_merge($errors, $validate);

        $validate = $this->validar_bodega_entrada($token, $detalles, $config);
        $errors = array_merge($errors, $validate);

        if($config['tipo'] == 'TRANSITO') {
            [$detalles, $validate] = $this->validar_bodega_transito($detalles, $bodegas, $config);
            $errors = array_merge($errors, $validate);
        }

        if($config['separar_traslados'] == 'SI'){
            $detalles = $this->separar_traslados($detalles);
        } else {
            $detalles = [$detalles];
        }

        if(!empty($errors)) {

            $contenido = $this->generar_archivo_errores($errors);

            return response($contenido)
                ->header('Content-Type', 'text/plain; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="errores_traslado.txt"');
        }

        $traslados = [];

        foreach ($detalles as $detalle) {
            $traslados[] = $this->traslado($token, $detalle, $config);
        }

        return $traslados;
    }

    private function buscar_producto(string $token, Collection $traslados)
    {
        $items = [];
        $validate = [];

        foreach($traslados as $row => $traslado) {
            if(!$traslado['codigo']) continue;
            $body = [
                'type' => 1,
                'browserID' => '33',
                'query' => $traslado['codigo'],
                'filter' => 'IsInventoryControl=1',
                'tags' => (object) []
            ];

            $response = Http::withToken($token)
                ->asJson()
                ->post(
                    'https://services.siigo.com/catalog/api/v1/Autocomplete/GetData',
                    $body
                );

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            $data = $response->json();
            $data = json_decode($data, true);
            $producto = collect($data)->where('Code', $traslado['codigo'])->first();

            if (!$producto) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $traslado['codigo'],
                    'Error' => 'El producto no existe o no tiene control de inventario',
                ];
                continue;
            }

            $items[] = [
                "ProductCode" => $producto['ProductID'],
                "Description" => $producto['Code'],
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

    private function validar_bodega_salida(string $token, array $detalles, array $config)
    {
        $validate = [];

        $fecha = Carbon::parse($config['fecha'])->format('Ymd');
        foreach($detalles as $row => $detalle) {
            $bodegas = [];

            foreach([0, 10, 20] as $numRecordView) {
                $body = [
                    'type' => 1,
                    'browserID' => '67',
                    'query' => '',
                    'filter' => "productcode = {$detalle['ProductCode']} AND period < CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'))",
                    "filter2" => "transactiondate >= CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'), '01') AND CONVERT(DATE, transactiondate, 120) <= CONVERT(DATE, '$fecha', 120) AND productcode = {$detalle['ProductCode']}",
                    "numRecordView" => $numRecordView,
                    'tags' => (object) [
                        "{FilterWareHouse}" => ""
                    ]
                ];

                $response = Http::withToken($token)
                    ->asJson()
                    ->post(
                        'https://services.siigo.com/catalog/api/v1/Autocomplete/GetData',
                        $body
                    );

                if (! $response->successful()) {
                    throw new \Exception($response->body());
                }

                $data = $response->json();
                $data = json_decode($data, true);

                $bodegas = array_merge($bodegas, $data);
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
        foreach($detalles as $row => $detalle) {
            $bodegas = [];

            foreach([0, 10, 20] as $numRecordView) {
                $body = [
                    'type' => 1,
                    'browserID' => '67',
                    'query' => '',
                    'filter' => "productcode = {$detalle['ProductCode']} AND period < CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'))",
                    "filter2" => "transactiondate >= CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'), '01') AND CONVERT(DATE, transactiondate, 120) <= CONVERT(DATE, '$fecha', 120) AND productcode = {$detalle['ProductCode']}",
                    "numRecordView" => $numRecordView,
                    'tags' => (object) [
                        "{FilterWareHouse}" => "{condition} ProductWarehouseId <> {$detalle['WarehouseCode']}"
                    ]
                ];

                $response = Http::withToken($token)
                    ->asJson()
                    ->post(
                        'https://services.siigo.com/catalog/api/v1/Autocomplete/GetData',
                        $body
                    );

                if (! $response->successful()) {
                    throw new \Exception($response->body());
                }

                $data = $response->json();
                $data = json_decode($data, true);

                $bodegas = array_merge($bodegas, $data);
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

    private function validar_bodega_transito(array $detalles, array $bodegas, array $config)
    {
        $validate = [];

        foreach($detalles as $row => $detalle) {
            $bodega = $bodegas['DIRECTO'][$detalle['DestinationWarehouseCode']] ?? null;

            if (!$bodega) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $detalle['DestinationWarehouseCode'],
                    'Error' => 'La bodega de entrada no está configurada para traslado',
                ];
            } else if(!$bodega['transito']) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $detalle['DestinationWarehouseCode'] . ' - ' . $bodega['name'],
                    'Error' => 'La bodega de entrada no tiene bodega de transito configurada',
                ];
            } else if($bodega['transito'] && !$bodegas['TRANSITO'][$bodega['transito']]) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['Description'],
                    'Description' => $detalle['LongDescription'],
                    'WarehouseCode' => $bodega['transito'],
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

    private function traslado(string $token, array $detalles, array $config)
    {
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
            ->post(
                'https://services.siigo.com/ACEntryApi/api/v1/WarehouseTransfer/Save/',
                $body
            );

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        return [
            'preview' => "https://siigonube.siigo.com/#/inventories/1372/{$data}",
            'preview_download' => "https://siigonube.siigo.com/#/inventories/1372/{$data}/true",
            'approved' => $config['tipo'] == 'TRANSITO' ? $this->generar_excel_aprobar_traslado_transito($data, $detalles) : null,
        ];
    }

    private function generar_excel_aprobar_traslado_transito(string|int $data, array $detalles)
    {
        $bodegas = $this->bodegas();
        $traslado = [
            [
                'usuario' => '',
                'contraseña' => '',
                'fecha' => '',
                'validar_disponible' => 'SI',
                'tipo' => 'DIRECTO',
                'separar_traslados' => 'NO',
                'observacion' => "APROBACION DE TRASLADO DE TRANSITO: https://siigonube.siigo.com/#/inventories/1372/{$data}",
            ]
        ];

        $bodega_salida = "";
        $bodega_entrada = "";

        $traslado_detalles = [];
        foreach ($detalles as $detalle) {
            $bodega_salida = $detalle['DestinationWarehouseCode'];
            $bodega_entrada = collect($bodegas['DIRECTO'])->search(function ($bodega) use ($detalle) { return $bodega['transito'] == $detalle['DestinationWarehouseCode'];});

            $traslado_detalles[] = [
                'codigo' => $detalle['Code'],
                'bodega_salida' => $bodega_salida,
                'bodega_entrada' => $bodega_entrada,
                'cantidad' => $detalle['Quantity'],
            ];
        }

        $filename = "masive_transfers/transfer_{$data}_" . now()->format('Y_m_d_His') . '.xlsx';

        Excel::store(
            new MasiveTransferSiigoMultiSheetExport(
                $traslado,
                $traslado_detalles
            ),
            "exports/{$filename}",
            'public'
        );

        $downloadUrl = route('exports.download', ['file' => $filename]);
        $name = $bodega_entrada . ' - ' . $bodegas['TRANSITO'][$bodega_entrada]['name'] . ' a ' . $bodega_salida . ' - ' . $bodegas['DIRECTO'][$bodega_salida]['name'];

        return [
            'name' => $name,
            'download_url' => $downloadUrl,
        ];
    }

    private function bodegas()
    {
        return [
            'DIRECTO' => [
                -1 => ['name' => 'Sin asignar', 'transito' => null],
                2  => ['name' => 'P R I N C I P A L', 'transito' => null],
                3  => ['name' => 'ALEGRA', 'transito' => null],
                4  => ['name' => 'PUNTO DE VENTA', 'transito' => null],
                9  => ['name' => 'MAYALES', 'transito' => null],
                13 => ['name' => 'PROD FABRICA', 'transito' => null],
                15 => ['name' => 'REVENT', 'transito' => null],
                16 => ['name' => 'MATERIA PRIMA', 'transito' => null],
                17 => ['name' => 'OCEAN MALL', 'transito' => null],
                19 => ['name' => 'NUESTRO', 'transito' => null],
                22 => ['name' => 'ALAMEDAS', 'transito' => null],
                24 => ['name' => 'PORTAL', 'transito' => null],
                27 => ['name' => 'CARIBE PLAZA 1', 'transito' => null],
                28 => ['name' => 'PLAZA DEL SOL', 'transito' => null],
                31 => ['name' => 'WEB', 'transito' => null],
                32 => ['name' => 'CASTELLANA', 'transito' => null],
                33 => ['name' => 'UNICO BQ', 'transito' => null],
                34 => ['name' => 'CREAR ENSAMBLES', 'transito' => null],
                35 => ['name' => 'Credito de Calzado', 'transito' => null],
                36 => ['name' => 'ECOMMERCE', 'transito' => null],
                37 => ['name' => 'CARNAVAL', 'transito' => null],
                44 => ['name' => 'INSTAGRAM', 'transito' => null],
                45 => ['name' => 'GARANTIAS', 'transito' => null],
                46 => ['name' => 'GUATAPURI', 'transito' => null],
                47 => ['name' => 'TEMPORADA 2025', 'transito' => null],
                49 => ['name' => 'VENTURA PLAZA', 'transito' => null],
                50 => ['name' => 'FABRICATO', 'transito' => null],
                51 => ['name' => 'UNICO CALI', 'transito' => null],
                56 => ['name' => 'CARIBE PLAZA 2', 'transito' => null],
                57 => ['name' => 'MAYORCA', 'transito' => null],
                58 => ['name' => 'GRAN MANZANA', 'transito' => null],
                59 => ['name' => 'NUESTRO ATLANTICO', 'transito' => null],
                62 => ['name' => 'NUESTRO CARTAGO', 'transito' => null],
                63 => ['name' => 'NUESTRO URABÁ', 'transito' => null]
            ],
            'TRANSITO' => [

            ]
        ];
    }

    private function generar_archivo_errores(array $errors)
    {
        $contenido = "ERRORES DE VALIDACION\n";
        $contenido .= "==========================\n\n";

        foreach ($errors as $error) {
            $contenido .= "Fila: " . ($error['Row'] ?? '') . "\n";
            if(isset($error['ProductCode'])) $contenido .= "Producto: " . ($error['ProductCode'] ?? '') . "\n";
            if(isset($error['Description'])) $contenido .= "Descripción: " . ($error['Description'] ?? '') . "\n";
            if(isset($error['WarehouseCode'])) $contenido .= "Bodega: " . ($error['WarehouseCode'] ?? '') . "\n";
            if(isset($error['AvailableQuantity'])) $contenido .= "Disponible: " . ($error['AvailableQuantity'] ?? 0) . "\n";
            if(isset($error['RequiredQuantity'])) $contenido .= "Requerido: " . ($error['RequiredQuantity'] ?? 0) . "\n";
            $contenido .= "Error: " . ($error['Error'] ?? '') . "\n";
            $contenido .= "-------------------------------------------------\n";
        }

        return $contenido;
    }
}

<?php

namespace App\Http\Controllers\Integration;

use App\Exports\PurchaseOrderSiigoMultiSheetExport;
use App\Http\Controllers\Controller;
use App\Imports\MasivePurchaseOrderSiigoSheetsImport;
use App\Services\SiigoInventoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PurchaseOrderSiigoController extends Controller
{
    public function purchase_order()
    {
        $purchase_order_types = [
            23200 => 'OC-1-Orden de compra principal',
            27279 => 'OC-2-Orden de servicio',
            28029 => 'OC-3-Orden Compra Internet',
            30480 => 'OC-4-O.C. Materia Prima',
        ];

        return view('integration.purchase_order', compact('purchase_order_types'));
    }

    public function purchase_order_format(Request $request)
    {
        $cookie = $request->input('cookie');
        preg_match('/TKNSGRDDREVENTCALZADOSAS=([^;]+)/', $cookie, $matches);

        $token = $matches[1] ?? null;
        $purchase_order_type_id = $request->input('purchase_order_type_id');

        $type_details = [
            '0' => 'Producto',
            '1' => 'Activo fijo',
            '2' => 'Gasto / Cuenta contable'
        ];

        $purchase_order_type = $this->purchase_order_type($token, $cookie, $purchase_order_type_id);

        $list_taxes = $this->list_taxes($token, $cookie);
        $cost_centers = $purchase_order_type->UseCostCenter ? $this->cost_centers() : [];
        $warehouses = $this->warehouses();

        $filters = [
            'use_cost_center' => $purchase_order_type->UseCostCenter,
            'is_rete_iva' => $purchase_order_type->IsReteIva,
            'is_rete_ica' => $purchase_order_type->IsReteIca
        ];

        return Excel::download(new PurchaseOrderSiigoMultiSheetExport($purchase_order_type, $filters, $warehouses, $cost_centers, $list_taxes, $type_details), "formato_{$purchase_order_type->ERPDocClass}_{$purchase_order_type->ERPDocCode}.xlsx");
    }

    public function purchase_order_upload(Request $request)
    {
        $request->validate([
            'file'  => 'required|file|mimes:xlsx,xls',
            'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@revent\.com\.co$/'],
        ], [
            'email.regex' => 'El correo :input debe pertenecer al dominio @revent.com.co',
        ]);

        $compras = [];
        $errors = [];

        $email = $request->input('email');

        $order = Excel::toCollection(new MasivePurchaseOrderSiigoSheetsImport, $request->file('file'));

        $config = $order['orden_compra'][0]->toArray();

        $cookie = $config['cookie'];

        if (empty($cookie)) {
            $errors[] = [
                'Row' => 'COOKIE',
                'Error' => 'La cookie es obligatorio',
            ];
            return $errors;
        }

        preg_match('/TKNSGRDDREVENTCALZADOSAS=([^;]+)/', $cookie, $matches);

        $token = $matches[1] ?? null;
        $purchase_order_type_id = $config['tipo'];

        if (empty($purchase_order_type_id)) {
            $errors[] = [
                'Row' => 'TIPO',
                'Error' => 'El tipo de orden de compra es obligatorio',
            ];
            return $errors;
        }

        $purchase_order_type = $this->purchase_order_type($token, $cookie, $purchase_order_type_id);

        if (empty($purchase_order_type)) {
            $errors[] = [
                'Row' => 'TIPO',
                'Error' => 'El tipo de orden de compra no es valido',
            ];
            return $errors;
        }

        if (isset($config['fecha']) && $config['fecha'] != '') {
            $fecha = $config['fecha'];
            if (is_numeric($fecha)) {
                $config['fecha'] = Date::excelToDateTimeObject($fecha)->format('Y-m-d');
            } else {
                $config['fecha'] = Carbon::createFromFormat('d/m/Y', trim($fecha))->format('Y-m-d');
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

        if($purchase_order_type->UseCostCenter) {
            if(empty($config['centro_costo'])){
                $errors[] = [
                    'Row' => 'CENTRO COSTO',
                    'Error' => 'El centro de costo es obligatorio',
                ];
            }

            $cost_centers = collect($this->cost_centers())->keyBy('id')->all();

            if(!isset($cost_centers[$config['centro_costo']])) {
                $errors[] = [
                    'Row' => 'CENTRO COSTO',
                    'Error' => 'El centro de costo no es valido',
                ];
            }
        }

        $list_taxes = $this->list_taxes($token, $cookie);

        if ($purchase_order_type->IsReteIva && isset($config['rete_iva'])) {
            $exists = collect($list_taxes['rete_iva'])->contains('Id', $config['rete_iva']);

            if (!$exists) {
                $errors[] = [
                    'Row' => 'RETE IVA',
                    'Error' => 'El rete iva no es valido',
                ];
            }
        }

        if ($purchase_order_type->IsReteIca && isset($config['rete_ica'])) {
            $exists = collect($list_taxes['rete_ica'])->contains('Id', $config['rete_ica']);

            if (!$exists) {
                $errors[] = [
                    'Row' => 'RETE ICA',
                    'Error' => 'El rete ica no es valido',
                ];
            }
        }
        if(!empty($errors)) return $errors;

        if(empty($config['proveedor'])){
            $errors[] = [
                'Row' => 'PROVEEDOR',
                'Error' => 'El proveedor es obligatorio',
            ];
            return $errors;
        }

        $search_providers = $this->search_providers($token, $cookie, $config['proveedor']);

        if($search_providers->count() > 1) {
            $errors[] = [
                'Row' => 'PROVEEDOR',
                'Error' => 'El proveedor no es valido. Multiples regsitros de busqueda',
            ];
            return $errors;
        } elseif($search_providers->isEmpty()) {
            $errors[] = [
                'Row' => 'PROVEEDOR',
                'Error' => 'El proveedor no es valido',
            ];
            return $errors;
        }

        $search_providers = $search_providers->first();
        $provider = $this->search_provider($token, $cookie, $search_providers['ID']);

        $warehouses = $this->warehouses();
        $warehouses = collect($warehouses)->keyBy('id')->all();

        $date = Carbon::createFromFormat('d/m/Y', trim($fecha))->format('Ymd');

        [$orden_compra_detalles, $errors] = $this->validar_detalles($token, $cookie, $order['orden_compra_detalles']->toArray(), $warehouses, $date, $list_taxes['imp_cargo'], $list_taxes['imp_retencion']);
        if(!empty($errors)) return $errors;
        
        return $orden_compra_detalles;



        return $errors;
    }

    private function validar_detalles(string $token, string $cookie, array $orden_compra_detalles, array $warehouses, string $date, array $imp_cargo, array $imp_retencion)
    {
        $validate = [];
        $detalles = [];

        $imp_cargo = collect($imp_cargo)->keyBy('Id')->all();
        $imp_retencion = collect($imp_retencion)->keyBy('Id')->all();

        foreach($orden_compra_detalles as $row => $detalle) {
            $warehouse = $warehouses[$detalle['bodega']] ?? [];

            if(empty($detalle['tipo_detalle'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El tipo es obligatorio',
                ];
            } elseif(!in_array($detalle['tipo_detalle'], ['0', '1', '2'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El tipo no es valido',
                ];
            }

            if(empty($detalle['item'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El item es obligatorio',
                ];
            }

            $search_product = $this->search_products($token, $cookie, $detalle['item']);
            $product = [];

            if(empty($search_product)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El item no es valido',
                ];
            } else {
                $product = $this->search_product($token, $cookie, $search_product['ID'], $date);
            }

            if (empty($product)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El producto no existe',
                ];
            }

            if(empty($detalle['bodega'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La bodega es obligatorio',
                ];
            } elseif(empty($warehouse)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La bodega no es valida',
                ];
            }

            if(empty($detalle['cantidad'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La cantidad es obligatorio',
                ];
            } elseif(!is_numeric($detalle['cantidad'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La cantidad debe ser un valor numerico',
                ];
            } elseif($detalle['cantidad'] == 0) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La cantidad debe ser mayor a 0',
                ];
            }

            if(empty($detalle['valor_unitario'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El valor unitario es obligatorio',
                ];
            } elseif(!is_numeric($detalle['valor_unitario'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El valor unitario debe ser un valor numerico',
                ];
            }

            $detalle['descuento'] = $detalle['descuento'] ?: 0;

            if(!is_numeric($detalle['descuento'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El descuento debe ser un valor numerico',
                ];
            } elseif(is_numeric($detalle['valor_unitario']) && is_numeric($detalle['descuento'])) {
                if($detalle['descuento'] > $detalle['valor_unitario']) {
                    $validate[] = [
                        'Row' => $row + 1,
                        'ProductCode' => $detalle['item'],
                        'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                        'Error' => 'El descuento no puede ser mayor al precio unitario',
                    ];
                }
            }

            if(empty($detalle['imp_cargo'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto cargo es obligatorio',
                ];
            } elseif(!isset($imp_cargo[$detalle['imp_cargo']])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto cargo no es valido',
                ];
            }

            if(empty($detalle['imp_retencion'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto retencion es obligatorio',
                ];
            } elseif(!isset($imp_retencion[$detalle['imp_retencion']])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto retencion no es valido',
                ];
            }

            $pWarehouseList = is_string($product['pWarehouseList'] ?? null) ? json_decode($product['pWarehouseList'], true) : ($product['pWarehouseList'] ?? []);
            $warehouseEntry = collect($pWarehouseList)->firstWhere('id', $detalle['bodega']);

            if (!$warehouseEntry) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La bodega no existe para este producto',
                ];
            }

            if(!empty($validate)) continue;

            $pPriceList = is_string($product['pPriceList'] ?? null) ? json_decode($product['pPriceList'], true) : ($product['pPriceList'] ?? []);
            $defaultMoneyList = collect($pPriceList)->firstWhere('MoneyCode', $product['defaultMoneyCode'] ?? 'COP');
            $priceList = $defaultMoneyList['PriceList'] ?? [];

            $TaxAdd = $imp_cargo[$detalle['imp_cargo']] ?? [];
            $TaxDisc = $imp_retencion[$detalle['imp_retencion']] ?? [];

            $grossValue = $detalle['valor_unitario'] * $detalle['cantidad'];
            $baseValue = $grossValue - ($detalle['descuento'] ?: 0);

            $add = $this->calculate_tax_add($detalle['valor_unitario'], $detalle['cantidad'], $detalle['descuento'], $TaxAdd);
            $disc = $this->calculate_tax_discount($detalle['valor_unitario'], $detalle['cantidad'], $detalle['descuento'], $TaxDisc);

            $totalValue = $baseValue - $disc['TaxDiscount_Value'] + $add['TaxAdd_Value'];
            $valueWithChargeTaxes = $baseValue + $add['TaxAdd_Value'];

            $pWarehouseList = json_decode($product['pWarehouseList']);

            $detalles[] = [
                "rowID" => $row + 1,
                "SelectedValue" => $product["pProductID"] ?: "",
                "SelectedCode" => $product["pCode"] ?: "",
                "SelectedName" => $product["pName"] ?: "",
                "Description" => $product["pDescription"] ?: "",
                "GrossValue" => $grossValue,
                "Quantity" => $detalle['cantidad'],
                "UndValue" => $detalle['valor_unitario'],
                "UndReaValue" => $detalle['valor_unitario'],
                "Discount" => $detalle['descuento'] ?: 0,
                "Discount_Value" => $detalle['descuento'] ?: 0,
                "BaseValue" => $baseValue,
                "IsAIU" => false,
                "AIU" => 0,
                "BaseAIU" => 0,
                "ProductSubType" => $product['pSubType'] ?: 0,
                "TaxDiscount_Name" => $disc['TaxDiscount_Name'],
                "TaxDiscount_Id" => $disc['TaxDiscount_Id'],
                "TaxDiscount_Value" => $disc['TaxDiscount_Value'],
                "TaxDiscount_Percentage" => $disc['TaxDiscount_Percentage'],
                "TotalValue" => $totalValue,
                "priceList" => $priceList,
                "Warehouse_Id" => $warehouseEntry['id'] ?? "-1",
                "Warehouse_Description" => $warehouseEntry['description'] ?? "",
                "Warehouse_Value" => $warehouseEntry['value'] ?? "0.00",
                "Warehouse_Code" => $warehouseEntry['code'] ?? "",
                "SalesmanCode" => "",
                "SalesmanName" => "",
                "SalesmanID" => "597",
                "AccountID" => -1,
                "AccountCode" => "",
                "AccountName" => "",
                "ProductIsInventoryControl" => $product['pIsInventoryControl'] ?? false,
                "IsInventoryControl" => $product['pIsInventoryControl'] ?? false,
                "TaxAdd_Name" => $add['TaxAdd_Name'],
                "TaxAdd_Id" => $add['TaxAdd_Id'],
                "TaxAdd_SubType" => $add['TaxAdd_SubType'],
                "TaxAdd_Value" => $add['TaxAdd_Value'],
                "TaxAdd_Percentage" => $add['TaxAdd_Percentage'],
                "TaxImpoValue" => 0,
                "TaxAdd2_Value" => 0,
                "ConsumptionValue" => 0,
                "ValueWithChargeTaxes" => $valueWithChargeTaxes,
                "ItemType" => $detalle['tipo_detalle'],
                "pWarehouseList" => $pWarehouseList
            ];
        }

        return [$detalles, $validate];
    }

    private function calculate_tax_add(int|float $value, int $quantity, int|float $discount, array $tax)
    {
        if (empty($tax) || $tax['Id'] == -1) {
            return [
                "TaxAdd_Name" => "",
                "TaxAdd_Id" => -1,
                "TaxAdd_SubType" => -1,
                "TaxAdd_Value" => 0,
                "TaxAdd_Percentage" => 0,
                "TaxAdd2_Value" => 0,
                "Value" => $value * $quantity,
            ];
        }

        $gross = $value * $quantity;
        $baseValue = $gross - $discount;
        $percentage = $tax['Value'];
        $taxValue = $baseValue * ($percentage / 100);

        return [
            "TaxAdd_Name" => $tax['Name'],
            "TaxAdd_Id" => $tax['Id'],
            "TaxAdd_SubType" => $tax['SubType'],
            "TaxAdd_Value" => $taxValue,
            "TaxAdd_Percentage" => $percentage,
            "TaxAdd2_Value" => 0,
            "Value" => $baseValue + $taxValue,
        ];
    }

    private function calculate_tax_discount(int|float $value, int $quantity, int|float $discount, array $tax)
    {
        if (empty($tax) || $tax['Id'] == -1) {
            return [
                "TaxDiscount_Name" => "",
                "TaxDiscount_Id" => -1,
                "TaxDiscount_Value" => 0,
                "TaxDiscount_Percentage" => 0,
            ];
        }

        $gross = $value * $quantity;
        $baseValue = $gross - $discount;
        $percentage = $tax['Value'];

        return [
            "TaxDiscount_Name" => $tax['Name'],
            "TaxDiscount_Id" => $tax['Id'],
            "TaxDiscount_Value" => $baseValue * ($percentage / 100),
            "TaxDiscount_Percentage" => $percentage,
        ];
    }

    private function search_products(string $token, string $cookie, string $text)
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->asMultipart()
            ->post('https://monolithprod.siigo.com/REVENTCALZADOSAS/Framework/Controls/AutoComplete.ashx', [
                ['name' => 'BrowseCode', 'contents' => '2103'],
                ['name' => 'SearchText', 'contents' => $text],
                ['name' => 'RequestWHERE', 'contents' => '&WHERE=Wkk9%2fo6RnE%2fyPn8MLfXxvwt2DDF53VToM8L2f%2fejoUR1sME0VEzILLHPQE8UvncakFqKBXIApFaXhy%2fpxHdOJw%3d%3d'],
                ['name' => 'txtAutoCompleteID', 'contents' => 'Default_ucControlPane0_ctl00_ERPInvoiceGrid_lkpProduct_txtAutoCompleteLookup'],
                ['name' => 'addRecordExtraParams', 'contents' => ''],
            ]);

        $data = $response->json();
        $data = collect($data)->where('Code', $text)->first();

        return $data;
    }

    private function search_product(string $token, string $cookie, string|int $product_id, string $date)
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->asMultipart()
            ->post('https://monolithprod.siigo.com/REVENTCALZADOSAS/Components/ERP/InvoiceHandler.ashx', [
                ['name' => 'ActionType', 'contents' => '2'],
                ['name' => 'ProductID', 'contents' => $product_id],
                ['name' => 'ERPDocType', 'contents' => '22'],
                ['name' => 'MoneyCode', 'contents' => ''],
                ['name' => 'DocDate', 'contents' => $date],
            ]);

        $data = $response->json();

        return $data;
    }

    private function search_providers(string $token, string $cookie, string $text)
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->asMultipart()
            ->post('https://monolithprod.siigo.com/REVENTCALZADOSAS/Framework/Controls/AutoComplete.ashx', [
                ['name' => 'BrowseCode', 'contents' => '1003'],
                ['name' => 'SearchText', 'contents' => $text],
                ['name' => 'RequestWHERE', 'contents' => '&WHERE=YSnns%2fo%2fazo1C3Mws6a3nso%2f%2bcyeS%2bwyVovfg9TCPOc%3d'],
                ['name' => 'txtAutoCompleteID', 'contents' => 'Default_ucControlPane0_ctl00_PurchaseOrderHeader_oACAccount_txtAutoCompleteLookup'],
                ['name' => 'addRecordExtraParams', 'contents' => 'TypesAsociates=3'],
            ]);

        $data = $response->json();
        $data = collect($data)
            ->reject(fn ($item) => $item['ID'] == -1)
            ->values();

        return $data;
    }

    private function search_provider(string $token, string $cookie, string|int $account_id)
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->asMultipart()
            ->post('https://monolithprod.siigo.com/REVENTCALZADOSAS/Components/ERP/Business/ERPDocHandler.ashx', [
                ['name' => 'Process', 'contents' => '4'],
                ['name' => 'AccountID', 'contents' => $account_id],
                ['name' => 'ERPDocType', 'contents' => '6'],
                ['name' => 'MoneyCode', 'contents' => ''],
            ]);

        $data = $response->json();

        return $data;
    }

    private function purchase_order_type(string $token, string $cookie, string|int $purchase_order_type_id)
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->asMultipart()
            ->post('https://monolithprod.siigo.com/REVENTCALZADOSAS/Components/ERP/Business/ERPDocHandler.ashx', [
                ['name' => 'Process', 'contents' => '3'],
                ['name' => 'ERPDocumentTypeID', 'contents' => $purchase_order_type_id],
                ['name' => 'ERPDocType', 'contents' => '0'],
                ['name' => 'ERPDocumentID', 'contents' => ''],
            ]);

        $data = json_decode($response->json('ERPConfigItem'));
        return $data;
    }

    private function list_taxes(string $token, string $cookie)
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->asMultipart()
            ->post('https://monolithprod.siigo.com/REVENTCALZADOSAS/Components/ERP/Business/ERPDocHandler.ashx', [
                ['name' => 'Process', 'contents' => '2'],
                ['name' => 'ERPDocType', 'contents' => '0'],
                ['name' => 'IsExpense', 'contents' => '1'],
                ['name' => 'ERPDocumentID', 'contents' => '-1'],
            ]);

        $data = $response->json();

        $impCargo = json_decode($data['lstTaxAdd'], true);
        $impRetencion = json_decode($data['lstTaxDisc'], true);

        $noAplica = ['Id' => -1, 'Name' => 'No aplica', 'Value' => -1, 'Type' => -1, 'SubType' => -1];

        $impCargo = array_merge([$noAplica], array_filter($impCargo, fn ($item) => $item['Id'] != -1));

        $impRetencion = array_merge([$noAplica], array_filter($impRetencion, fn ($item) => $item['Id'] != -1));

        return [
            'imp_cargo' => $impCargo,
            'imp_retencion' => $impRetencion,
            'rete_iva' => json_decode($data['lstTaxRetIVA'], true),
            'rete_ica' => json_decode($data['lstTaxRetICA'], true),
        ];
    }

    private function cost_centers(): array
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $response = Http::retry(5, 10000)->withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id'    => 'consultadeFacturas',
        ])->get("https://api.siigo.com/v1/cost-centers");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        return $data;
    }

    private function warehouses(): array
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $response = Http::retry(5, 10000)->withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id'    => 'consultadeFacturas',
        ])->get("https://api.siigo.com/v1/warehouses");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        $data = collect($data)
            ->reject(fn ($item) => str_starts_with(strtoupper($item['name']), 'TRANSITO'))
            ->values()
            ->all();

        $data = [['id' => '-1', 'name' => 'Sin asignar'], ...$data];
        return $data;
    }
}

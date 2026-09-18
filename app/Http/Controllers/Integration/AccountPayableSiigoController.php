<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Mail\AccountPayableAccessLink;
use App\Mail\AccountPayableAdvanceProviderSiigo;
use App\Mail\AccountPayableConciliationProviderSiigo;
use App\Mail\AccountPayablePaymentProviderSiigo;
use App\Services\SiigoInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AccountPayableSiigoController extends Controller
{
    private const DISK = 'public';
    private const BASE_PATH = 'vouchers';
    private const ACCOUNT_PAYABLE_ALLOWED_EMAILS = [
        'contabilidad@revent.com.co',
        'tecnologia@revent.com.co',
        'reventcalzado@gmail.com'
    ];

    public function account_payable_access()
    {
        return view('integration.account_payable_access');
    }

    public function account_payable_send_access_link(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->input('email')));

        if (!in_array($email, self::ACCOUNT_PAYABLE_ALLOWED_EMAILS, true)) {
            return back()
                ->withErrors(['email' => 'Este correo no tiene autorización para acceder a cuentas por pagar.'])
                ->withInput();
        }

        try {
            $url = URL::temporarySignedRoute('siigo.account_payable', now()->addHours(24));

            Mail::to($email)->send(new AccountPayableAccessLink($url));
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['email' => 'No fue posible enviar el enlace de acceso. Intenta nuevamente en unos minutos.'])
                ->withInput();
        }

        return back()->with('status', 'Te enviamos el enlace de acceso a ' . $email . '. Revisa tu bandeja de entrada (y spam).');
    }

    public function account_payable()
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $type_receipt = $this->type_receipt($token, '23202');

        $bank_accounts = $this->bank_accounts($token);

        $providers = $this->accounts_payable_providers($token);

        $type_documents = $this->type_documents($token);

        /*foreach ($providers as &$provider) {
            $uuid = $provider['MsThirdPartyID'] ?? null;

            if (!$uuid) {
                $provider['CompanyName'] = null;
                continue;
            }

            $cacheKey = 'siigo_provider_company_name_' . $uuid;
            $companyName = Cache::get($cacheKey);
            if ($companyName === null) {
                $data = $this->provider($token, $uuid);
                $companyName = data_get($data, 'BasicData.CompanyName');

                if ($companyName !== null) {
                    Cache::put($cacheKey, $companyName, now()->addDays(7));
                }
            }

            $provider['CompanyName'] = $companyName;
        }

        unset($provider);*/

        return view('integration.account_payable', compact( 'providers', 'type_receipt', 'bank_accounts', 'type_documents'));
    }

    public function account_payable_documents(Request $request, int $accountId)
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        if($request->input('type') == 'payment') {

            $payments = $this->payments_documents($token, $accountId);
            $payments = collect($payments)
                ->map(function ($payment) use ($token) {
                    $detail = $this->payment_entry($token, (int) $payment['ACEntryID']);
                    $payment['Link'] = "https://siigonube.siigo.com/#/paymentsv2/1016/view/{$payment['ACEntryID']}";
                    $payment['Detail'] = $detail;

                    return $payment;
                })->values()->toArray();

            return response()->json([
                'payments' => $payments,
            ]);
        }

        $allData = $request->boolean('all_data');

        $warehousesById = collect();
        if ($allData) {
            $warehouses = $this->warehouses($token);
            $warehousesById = collect($warehouses)->keyBy('id');
        }

        $documents = $this->accounts_payable_documents($token, $accountId);
        $purchases = $this->purchases_documents($token, $accountId);

        $purchasesByExternalDocument = collect($purchases)->keyBy('ExternalDocumentNumber');

        $documents = collect($documents)
            ->map(function ($document) use ($purchasesByExternalDocument, $token, $accountId, $allData, $warehousesById) {
                $purchase = $purchasesByExternalDocument->get($document['DueName']);

                $document['IsAnnulled'] = $purchase['IsAnnulled'] ?? false;
                $document['DocName'] = $purchase['DocName'] ?? null;
                $document['TotalValue'] = $purchase['TotalValue'] ?? null;
                $document['ACEntryID'] = $purchase['ACEntryID'] ?? null;

                if ($allData) {
                    if (!empty($document['ACEntryID'])) {
                        $purchase_entry = $this->purchase_entry($token, $accountId, (int) $document['ACEntryID']);

                        $purchase_entry_detail = $this->purchase_entry_detail($token, (int) $document['ACEntryID']);

                        $document['PurchaseEntry'] = $purchase_entry;

                        $document['PurchaseEntryDetail'] = [
                            'Observations' => $purchase_entry_detail['Observations'],
                            'WarehouseCodes' => collect($purchase_entry_detail['WarehouseCodes'])
                                ->map(function ($warehouseCode) use ($warehousesById) {
                                    $warehouse = $warehousesById->get($warehouseCode);
                                    return $warehouseCode . '-' . ($warehouse['name'] ?? '');
                                })
                                ->implode(', '),
                            'Quantity' => $purchase_entry_detail['Quantity'],
                        ];
                    } else {
                        $document['PurchaseEntry'] = [
                            'quotationID' => null,
                            'docName' => null
                        ];
                        $document['PurchaseEntryDetail'] = [
                            'Observations' => '',
                            'WarehouseCodes' => '',
                            'Quantity' => 0
                        ];
                    }
                }

                $document['Links'] = [];
                if($document['ACEntryID'] ?? null) {
                    $document['Links']['PurchaseInvoice'] = "https://siigonube.siigo.com/#/purchase/1008/{$document['ACEntryID']}";
                }

                if($document['PurchaseEntry']['quotationID'] ?? null) {
                    $encodedUrl = base64_encode("Default.aspx?TabID=1671&ERPDocumentID={$document['PurchaseEntry']['quotationID']}");
                    $document['Links']['PurchaseOrder'] = "https://siigonube.siigo.com/#/asp/{$encodedUrl}?TabID=1671";
                }

                return $document;
            })
            ->values()
            ->all();

        return response()->json([
            'documents' => $documents,
        ]);
    }

    public function accounts_payment(Request $request)
    {
        try {
            $request->validate([
                'proveedor' => 'required',
                'tipo' => 'required|integer',
                'accion' => 'required|string',
                'origen' => 'required|integer',
                'fecha' => 'required|date',
                'observaciones' => 'nullable|string',
                'valor' => 'required|numeric|min:0.01',
                'documentos' => 'required|string',
                'comprobante' => 'nullable|file|max:10240',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);
        }
        $proveedor = json_decode($request->proveedor, true);
        $documentos = json_decode($request->input('documentos'), true);

        if (!is_array($documentos) || empty($documentos)) {
            return response()->json([
                'message' => 'Debes seleccionar al menos un documento a pagar.',
            ], 422);
        }

        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $warehouses = $this->warehouses($token);
        $warehousesById = collect($warehouses)->keyBy('id');

        $type_receipt = $this->type_receipt($token, '23202');
        $EntryType = [
            "ERPDocumentTypeID" => $type_receipt['ERPDocumentTypeId'],
            "Name" => $type_receipt['Title'],
            "Class" => $type_receipt['DocClass'],
            "Code" => $type_receipt['Code'],
            "ACAccountCode" => -1,
            "CostCenterDefault" => $type_receipt['CostCenterDefault'],
            "CostCenterMandatory" => $type_receipt['CostCenterMandatory'],
            "InternalDescription" => $type_receipt['InternalDescription'],
            "IsAutomaticEnum" => $type_receipt['IsAutomaticEnum'],
            "TemplateName" => $type_receipt['TemplateName'],
            "UseCostCenter" => $type_receipt['UseCostCenter']
        ];

        $accountId = $proveedor['AccountID'];
        $observaciones = $request->input('observaciones');
        $Entry = [
            "ACEntryID" => -1,
            "DocNumber" => -1,
            "DocName" => "",
            "Observations" => $observaciones,
            "DocDate" => Carbon::parse($request->input('fecha'))->format('Ymd'),
            "ACEntryCode" => -1,
            "ACPaymentMeanCode" => $request->integer('origen'),
            "AccountCode" => $proveedor['AccountID'],
            "AttachmentsFSItemsGUID" => "",
            "ExchangePersonalized" => false,
            "ExchangeValue" => 0,
            "ExtendsFields" => "",
            "ForeignMoneyCode" => "",
            "IsClosePeriod" => false,
            "PaymentMethod" => null,
            "PaymentMethodCFDI" => null,
            "SourceType" => 0,
            "TotalValue" => (float) $request->input('valor'),
            "VoucherType" => $request->input('accion')
        ];

        $Items = $this->accounts_provider($token, $accountId, $documentos);

        if (empty($Items)) {
            return response()->json([
                'message' => 'No se encontraron documentos para el proveedor y los documentos especificados.',
            ], 404);
        }

        if (round((float) $request->input('valor'), 2) > round((float) collect($Items)->sum('Value'), 2)) {
            return response()->json([
                'message' => 'El valor total de los documentos seleccionados no coincide con el valor a pagar.',
            ], 400);
        }

        $AttachFiles = [];
        $url = null;
        if ($request->hasFile('comprobante')) {
            $parentPathGuid = (string) Str::uuid();

            $photo = $request->file('comprobante');
            $filename = $parentPathGuid . '.' . $photo->getClientOriginalExtension();
            $path = self::BASE_PATH;
            Storage::disk(self::DISK)->putFileAs($path, $photo, $filename);
            $url = Storage::disk(self::DISK)->url("{$path}/{$filename}");

            $uploaded = $this->upload_attachment($token, $parentPathGuid, $request->file('comprobante'));
            $AttachFiles[] = [
                "Name" => $uploaded['Name'] ?? $request->file('comprobante')->getClientOriginalName(),
                "GUID" => $uploaded['GUID'] ?? null,
                "Extension" => $uploaded['Extension'] ?? ('.' . $request->file('comprobante')->getClientOriginalExtension()),
                "Type" => $uploaded['Type'] ?? '-image',
                "shortName" => Str::limit($uploaded['Name'] ?? '', 20, '...'),
            ];

            $this->update_attach_files_references($token, $AttachFiles);
        }

        $this->validate_entry($token, $Entry['DocDate'], $request->integer('tipo'), -1, -1);

        $payload = [
            "AttachFiles" => $AttachFiles,
            "Entry" => $Entry,
            "EntryType" => $EntryType,
            "Items" => $Items,
            "ModelType" => 4,
            "TreasuryPayment" => null,
        ];

        foreach($documentos as &$item) {
            $purchase_entry = $this->purchase_entry($token, $accountId, (int) $item['doc']['ACEntryID']);
            $purchase_entry_detail = $this->purchase_entry_detail($token, (int) $item['doc']['ACEntryID']);
            $item['doc']['PurchaseEntry'] = $purchase_entry;
            $item['doc']['PurchaseEntryDetail'] = [
                'DocDate' => $purchase_entry_detail['DocDate'],
                'Observations' => $purchase_entry_detail['Observations'],
                'WarehouseCodes' => collect($purchase_entry_detail['WarehouseCodes'])
                    ->map(function ($warehouseCode) use ($warehousesById) {
                        $warehouse = $warehousesById->get($warehouseCode);
                        return $warehouseCode . '-' . ($warehouse['name'] ?? '');
                    })
                    ->implode(', '),
                'Quantity' => $purchase_entry_detail['Quantity'],
            ];
        }

        $provider = $this->provider($token, $proveedor['MsThirdPartyID']);

        $voucher_id = $this->save_voucher($token, $payload);

        $voucher = $this->search_voucher($token, $voucher_id);

        $firma =  [
            'nombre' => 'Ninoska Fontalvo',
            'cargo' => 'Auxiliar administrativo',
            'departamento' => 'Departamento de Cartera',
            'empresa' => 'Revent Calzado SAS',
            'celular' => '3222792893',
        ];

        $emails = []/*collect($provider['Contacts'])->pluck('Email')->filter()->unique()->values()->toArray()*/;
        Mail::to(['contabilidad@revent.com.co', ...$emails])->send(new AccountPayablePaymentProviderSiigo($provider, $voucher, $documentos, $firma, $observaciones, $voucher_id, $url));

        return response()->json([
            'success' => true,
            'message' => 'Recibo de pago registrado correctamente.',
            'voucher_id' => $voucher_id,
            'voucher' => $voucher,
        ]);
    }

    public function accounts_advance(Request $request)
    {
        try {
            $request->validate([
                'proveedor' => 'required',
                'tipo' => 'required|integer',
                'accion' => 'required|string',
                'origen' => 'required|integer',
                'fecha' => 'required|date',
                'observaciones' => 'nullable|string',
                'valor' => 'required|numeric|min:0.01',
                'comprobante' => 'nullable|file|max:10240',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);
        }
        $proveedor = json_decode($request->proveedor, true);

        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $type_receipt = $this->type_receipt($token, '23202');
        $EntryType = [
            "ERPDocumentTypeID" => $type_receipt['ERPDocumentTypeId'],
            "Name" => $type_receipt['Title'],
            "Class" => $type_receipt['DocClass'],
            "Code" => $type_receipt['Code'],
            "ACAccountCode" => -1,
            "CostCenterDefault" => $type_receipt['CostCenterDefault'],
            "CostCenterMandatory" => $type_receipt['CostCenterMandatory'],
            "InternalDescription" => $type_receipt['InternalDescription'],
            "IsAutomaticEnum" => $type_receipt['IsAutomaticEnum'],
            "TemplateName" => $type_receipt['TemplateName'],
            "UseCostCenter" => $type_receipt['UseCostCenter']
        ];

        $observaciones = $request->input('observaciones');
        $Entry = [
            "ACEntryID" => -1,
            "DocNumber" => -1,
            "DocName" => "",
            "Observations" => $observaciones,
            "DocDate" => Carbon::parse($request->input('fecha'))->format('Ymd'),
            "ACEntryCode" => -1,
            "ACPaymentMeanCode" => $request->integer('origen'),
            "AccountCode" => $proveedor['AccountID'],
            "AttachmentsFSItemsGUID" => "",
            "ExchangePersonalized" => false,
            "ExchangeValue" => 0,
            "ExtendsFields" => "",
            "ForeignMoneyCode" => "",
            "IsClosePeriod" => false,
            "PaymentMethod" => null,
            "PaymentMethodCFDI" => null,
            "SourceType" => 0,
            "TotalValue" => (float) $request->input('valor'),
            "VoucherType" => $request->input('accion')
        ];

        $Items = [];

        $AttachFiles = [];
        $url = null;
        if ($request->hasFile('comprobante')) {
            $parentPathGuid = (string) Str::uuid();

            $photo = $request->file('comprobante');
            $filename = $parentPathGuid . '.' . $photo->getClientOriginalExtension();
            $path = self::BASE_PATH;
            Storage::disk(self::DISK)->putFileAs($path, $photo, $filename);
            $url = Storage::disk(self::DISK)->url("{$path}/{$filename}");

            $uploaded = $this->upload_attachment($token, $parentPathGuid, $request->file('comprobante'));
            $AttachFiles[] = [
                "Name" => $uploaded['Name'] ?? $request->file('comprobante')->getClientOriginalName(),
                "GUID" => $uploaded['GUID'] ?? null,
                "Extension" => $uploaded['Extension'] ?? ('.' . $request->file('comprobante')->getClientOriginalExtension()),
                "Type" => $uploaded['Type'] ?? '-image',
                "shortName" => Str::limit($uploaded['Name'] ?? '', 20, '...'),
            ];

            $this->update_attach_files_references($token, $AttachFiles);
        }

        $this->validate_entry($token, $Entry['DocDate'], $request->integer('tipo'), -1, -1);

        $payload = [
            "AttachFiles" => $AttachFiles,
            "Entry" => $Entry,
            "EntryType" => $EntryType,
            "Items" => $Items,
            "ModelType" => 4,
            "TreasuryPayment" => null,
        ];

        $provider = $this->provider($token, $proveedor['MsThirdPartyID']);

        $voucher_id = $this->save_voucher($token, $payload);

        $voucher = $this->search_voucher($token, $voucher_id);

        $firma =  [
            'nombre' => 'Ninoska Fontalvo',
            'cargo' => 'Auxiliar administrativo',
            'departamento' => 'Departamento de Cartera',
            'empresa' => 'Revent Calzado SAS',
            'celular' => '3222792893',
        ];

        $emails = []/*collect($provider['Contacts'])->pluck('Email')->filter()->unique()->values()->toArray()*/;
        Mail::to(['contabilidad@revent.com.co', ...$emails])->send(new AccountPayableAdvanceProviderSiigo($provider, $voucher, $firma, $observaciones, $voucher_id, $url));

        return response()->json([
            'success' => true,
            'message' => 'Recibo de anticipo registrado correctamente.',
            'voucher_id' => $voucher_id,
            'voucher' => $voucher,
        ]);
    }

    public function accounts_conciliation(Request $request)
    {
        try {
            $request->validate([
                'proveedor' => 'required',
                'tipo' => 'required|integer',
                'fecha' => 'required|date',
                'observaciones' => 'nullable|string',
                'documentos' => 'required|string',
                'recibos' => 'required|string'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);
        }
        $proveedor = json_decode($request->proveedor, true);
        $documentos = json_decode($request->documentos, true);
        $recibos = json_decode($request->recibos, true);

        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $warehouses = $this->warehouses($token);
        $warehousesById = collect($warehouses)->keyBy('id');

        $type_receipt = $this->type_receipt($token, $request->input('tipo'));

        $account_documentos = $this->search_account($token, '22050501');
        $lista_documentos = $this->search_list($token, $account_documentos['ACAccountID'], $proveedor['AccountID'], collect($documentos)->pluck('doc.DueName')->toArray());

        $account_recibos = $this->search_account($token, '13300501');
        $lista_recibos = $this->search_list($token, $account_recibos['ACAccountID'], $proveedor['AccountID'], collect($recibos)->pluck('doc.DueName')->toArray());

        $EntryType = [
            "ApplyAccountingBook" => $type_receipt['ApplyAccountingBook'],
            "Class" => $type_receipt['DocClass'],
            "Code" => $type_receipt['Code'],
            "Consecutive" => $type_receipt['Consecutive'],
            "CostCenterDefault" => $type_receipt['CostCenterDefault'],
            "CostCenterDefaultCode" => $type_receipt['CostCenterDefault'],
            "CostCenterMandatory" => $type_receipt['CostCenterMandatory'],
            "DocNumber" => $type_receipt['Consecutive'],
            "ERPDocumentTypeID" => $type_receipt['ERPDocumentTypeId'],
            "Name" => $type_receipt['Title'],
            "Prefix" => $type_receipt['Prefix'],
            "PrintMsg" => $type_receipt['PrintMsg'],
            "TemplateName" => $type_receipt['TemplateName'],
            "UseCostCenter" => $type_receipt['UseCostCenter'],
            "UseDocumentSupport" => $type_receipt['UseDocumentSupport']
        ];

        $accountId = $proveedor['AccountID'];
        $observaciones = $request->input('observaciones');
        $Entry = [
            "ACEntryID" => -1,
            "AttachmentsFSItemsGUID" => "",
            "CashBookData" => null,
            "DocDate" => Carbon::parse($request->input('fecha'))->format('Ymd'),
            "DocName" => "",
            "ExchangePersonalized" => false,
            "ExchangeValue" => 0,
            "ExemptBase" => 0,
            "ExtendsFields" => "",
            "ForeignMoneyCode" => "",
            "Observations" => $observaciones,
            "OriginSource" => 0,
            "PayrollPeriodCode" => -1,
            "PayrollVoucherType" => -1,
            "PolicyType" => null,
            "SourceType" => 0,
            "TaxableBase" => 0,
            "TotalValue" => (float) collect($documentos)->sum('covered'),
        ];

        $order = 1;
        $Items = [];
        foreach ($lista_documentos as $lista_documento) {
            $Items[] = [
                "ACAccountCode" => $lista_documento['ACAccountCode'],
                "ACPaymentMean" => null,
                "AccountCode" => $lista_documento['AccountCode'],
                "BaseValue" => 0,
                "ConsumptionValue" => 0,
                "CostCenterCode" => $lista_documento['CostCenterCode'],
                "DC" => 1,
                "Description" => "{$lista_documento['DuePrefix']}-{$lista_documento['DueConsecutive']} Cuota: {$lista_documento['DueQuote']} Fecha: {$lista_documento['DueDate']}",
                "DueConsecutive" => $lista_documento['DueConsecutive'],
                "DueDate" => $lista_documento['DueDateFormat'],
                "DuePrefix" => $lista_documento['DuePrefix'],
                "DueQuote" => $lista_documento['DueQuote'],
                "EntryItemType" => 0,
                "FixedAssetCode" => null,
                "LongDescription" => $account_documentos['Name'],
                "Order" => $order,
                "ProductCode" => null,
                "Quantity" => 0,
                "TaxAddCode" => null,
                "TaxAddName" => "",
                "TaxAddPercentage" => 0,
                "TaxAddValue" => 0,
                "TaxDiscCode" => null,
                "TaxDiscName" => "",
                "TaxDiscPercentage" => 0,
                "TaxDiscValue" => 0,
                "Value" => collect($documentos)->firstWhere('doc.DueName', $lista_documento['DuePrefix'].'-'.$lista_documento['DueConsecutive'])['covered'] ?? 0,
            ];
            $order++;
        }
        foreach ($lista_recibos as $lista_recibo) {
            $Items[] = [
                "ACAccountCode" => $lista_recibo['ACAccountCode'],
                "ACPaymentMean" => null,
                "AccountCode" => $lista_recibo['AccountCode'],
                "BaseValue" => 0,
                "ConsumptionValue" => 0,
                "CostCenterCode" => $lista_recibo['CostCenterCode'],
                "DC" => -1,
                "Description" => "{$lista_recibo['DuePrefix']}-{$lista_recibo['DueConsecutive']} Cuota: {$lista_recibo['DueQuote']} Fecha: {$lista_recibo['DueDate']}",
                "DueConsecutive" => $lista_recibo['DueConsecutive'],
                "DueDate" => $lista_recibo['DueDateFormat'],
                "DuePrefix" => $lista_recibo['DuePrefix'],
                "DueQuote" => $lista_recibo['DueQuote'],
                "EntryItemType" => 0,
                "FixedAssetCode" => null,
                "LongDescription" => $account_recibos['Name'],
                "Order" => $order,
                "ProductCode" => null,
                "Quantity" => 0,
                "TaxAddCode" => null,
                "TaxAddName" => "",
                "TaxAddPercentage" => 0,
                "TaxAddValue" => 0,
                "TaxDiscCode" => null,
                "TaxDiscName" => "",
                "TaxDiscPercentage" => 0,
                "TaxDiscValue" => 0,
                "Value" => collect($recibos)->firstWhere('doc.DueName', $lista_recibo['DuePrefix'].'-'.$lista_recibo['DueConsecutive'])['used'] ?? 0,
            ];
            $order++;
        }

        $AttachFiles = [];

        $this->validate_entry($token, $Entry['DocDate'], $request->integer('tipo'), -1, -1);

        $payload = [
            "AttachFiles" => $AttachFiles,
            "Entry" => $Entry,
            "EntryType" => $EntryType,
            "Items" => $Items,
            "ModelType" => 21
        ];

        foreach($documentos as &$item) {
            $purchase_entry = $this->purchase_entry($token, $accountId, (int) $item['doc']['ACEntryID']);
            $purchase_entry_detail = $this->purchase_entry_detail($token, (int) $item['doc']['ACEntryID']);
            $item['doc']['PurchaseEntry'] = $purchase_entry;
            $item['doc']['PurchaseEntryDetail'] = [
                'DocDate' => $purchase_entry_detail['DocDate'],
                'Observations' => $purchase_entry_detail['Observations'],
                'WarehouseCodes' => collect($purchase_entry_detail['WarehouseCodes'])
                    ->map(function ($warehouseCode) use ($warehousesById) {
                        $warehouse = $warehousesById->get($warehouseCode);
                        return $warehouseCode . '-' . ($warehouse['name'] ?? '');
                    })
                    ->implode(', '),
                'Quantity' => $purchase_entry_detail['Quantity'],
            ];
        }

        $provider = $this->provider($token, $proveedor['MsThirdPartyID']);

        $voucher = $this->save_conciliation($token, $payload);

        $voucher_id = $voucher['code'];

        $firma =  [
            'nombre' => 'Ninoska Fontalvo',
            'cargo' => 'Auxiliar administrativo',
            'departamento' => 'Departamento de Cartera',
            'empresa' => 'Revent Calzado SAS',
            'celular' => '3222792893',
        ];

        $emails = []/*collect($provider['Contacts'])->pluck('Email')->filter()->unique()->values()->toArray()*/;
        Mail::to(['contabilidad@revent.com.co', ...$emails])->send(new AccountPayableConciliationProviderSiigo($provider, $voucher, $documentos, $recibos, $firma, $observaciones, $voucher_id));

        return response()->json([
            'success' => true,
            'message' => 'Cruce contable registrado correctamente.',
            'voucher_id' => $voucher_id,
            'voucher' => $voucher,
        ]);
    }

    private function provider(string $token, string $uuid)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(30)
            ->timeout(600)
            ->get("https://services.siigo.com/catalog/api/third-party/account/{$uuid}");

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando balance proveedor: ' . $response->body()
            );
        }

        $data = $response->json('AccountDto');

        return $data;
    }

    private function accounts_provider(string $token, int $accountId, array $documentos)
    {

        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(30)
            ->timeout(600)
            ->get('https://services.siigo.com/ACEntryApi/api/v1/Voucher/GetDuesClient', [
                'id' => $accountId,
                'moneyid' => '',
                'voucherType' => 0,
                'modeltype' => 4
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando balance proveedor: ' . $response->body()
            );
        }

        $data = json_decode($response->json('dataJSONResult'), true);

        if (!is_array($data)) {
            throw new \Exception('Siigo no devolvió documentos válidos para este proveedor.');
        }

        $documentosEncontrados = collect($data)
            ->filter(function ($item) use ($documentos) {
                $documento = ($item['DuePrefix'] ?? '') . '-' . ($item['DueConsecutive'] ?? '');

                return in_array($documento, collect($documentos)->pluck('doc.DueName')->toArray());
            })
            ->map(function ($item) use ($documentos){
                $item['Value'] = collect($documentos)->firstWhere('doc.DueName', $item['DuePrefix'].'-'.$item['DueConsecutive'])['covered'] ?? 0;
                return $item;
            })
            ->values()
            ->toArray();

        return $documentosEncontrados;
    }

    private function upload_attachment(string $token, string $parentPathGuid, $file, int $acEntryCode = -1, int $referenceType = 216)
    {
        $url = 'https://services.siigo.com/isiigo2ACDocumentAPI/api/v1/Document/UploadFile?' . http_build_query([
            'parentPathGUID' => $parentPathGuid,
        ]);

        $response = Http::withToken($token)
            ->timeout(600)
            ->connectTimeout(30)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post($url, [
                'ACEntryCode' => $acEntryCode,
                'ReferenceType' => $referenceType,
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error subiendo el archivo adjunto: ' . $response->body()
            );
        }

        $data = $response->json();

        if (!($data['success'] ?? false)) {
            throw new \Exception('Siigo rechazó la subida del comprobante: ' . $response->body());
        }

        return $data;
    }

    private function update_attach_files_references(string $token, array $attachFiles, $entryCode = -1, int $referenceType = 216)
    {
        $body = [
            'AttachFiles' => $attachFiles,
            'EntryCode' => (string) $entryCode,
            'ReferenceType' => (string) $referenceType,
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->connectTimeout(30)
            ->post('https://services.siigo.com/ACEntryApi/api/v2/Invoice/UpdateReferecesAttachFiles', $body);

        if (!$response->successful()) {
            throw new \Exception(
                'Error actualizando referencias de archivos adjuntos: ' . $response->body()
            );
        }

        return $response->json();
    }

    private function validate_entry(string $token, string $date, int $erpDocumentTypeId, int $consecutive = -1, int $acEntryId = -1)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->connectTimeout(30)
            ->get('https://services.siigo.com/ACEntryApi/api/v1/ACEntryValidator/IsValid', [
                'Date' => $date,
                'ERPDocumentTypeID' => $erpDocumentTypeId,
                'Consecutive' => $consecutive,
                'ACEntryID' => $acEntryId,
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error validando el comprobante: ' . $response->body()
            );
        }

        $data = $response->json();

        if (!($data['success'] ?? false)) {
            throw new \Exception('El comprobante no pudo ser validado por Siigo.');
        }

        if ($data['inUse'] ?? false) {
            throw new \Exception('El consecutivo del comprobante ya se encuentra en uso, intenta de nuevo.');
        }

        return $data;
    }

    private function save_voucher(string $token, array $payload)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->connectTimeout(30)
            ->post('https://services.siigo.com/ACEntryApi/api/v1/Voucher/Save/', $payload);

        if (!$response->successful()) {
            throw new \Exception(
                'Error guardando el comprobante de pago: ' . $response->body()
            );
        }

        return $response->json();
    }

    private function save_conciliation(string $token, array $payload)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->connectTimeout(30)
            ->post('https://services.siigo.com/ACEntryApi/api/v1/JournalEntry/Save/', $payload);

        if (!$response->successful()) {
            throw new \Exception(
                'Error guardando el comprobante de contable: ' . $response->body()
            );
        }

        return $response->json();
    }

    private function search_voucher(string $token, int $id)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->connectTimeout(30)
            ->get('https://services.siigo.com/ACEntryApi/api/v1/Invoice/GetDataView', [
                'id' => $id,
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando el comprobante de pago: ' . $response->body()
            );
        }

        return $response->json();
    }

    private function accounts_payable_providers(string $token)
    {
        $filterCriterias = [
            [
                'Field' => '_AccountID',
                'FilterType' => 6,
                'OperatorType' => 0,
                'Value' => [],
                'ValueUI' => '',
                'Source' => '1',
            ],
            [
                'Field' => 'Currency',
                'FilterType' => 65,
                'OperatorType' => 0,
                'Value' => ['ALL'],
                'ValueUI' => 'Moneda Local',
                'Source' => null,
            ],
        ];

        $body = [
            'Id' => 5438,
            'Skip' => 0,
            'Take' => 0,
            'Sort' => ' ',
            'FilterCriterias' => json_encode($filterCriterias),
            'Params' => json_encode([
                'TabID' => '1630',
                'DUETYPE' => '-1',
                'pTabID' => '1630',
                'rReport' => '1',
            ]),
            'GetTotalCount' => false,
            'GridOrderCriteria' => null,
            'AddOns' => [],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->connectTimeout(30)
            ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando cuentas por pagar a proveedores: ' . $response->body()
            );
        }

        return $response->json('data.Value.Table');
    }

    private function accounts_payable_documents(string $token, int $accountId)
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        do {
            $filterCriterias = [
                [
                    'Field' => '_vClientProv',
                    'FilterType' => 68,
                    'OperatorType' => 0,
                    'Value' => [$accountId],
                    'ValueUI' => '',
                    'Source' => 'Account',
                ],
                [
                    'Field' => '_vDueAgeEnum',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => [-1],
                    'ValueUI' => '',
                    'Source' => 'DueAgeEnum',
                ],
                [
                    'Field' => 'Currency',
                    'FilterType' => 65,
                    'OperatorType' => 0,
                    'Value' => ['ALL'],
                    'ValueUI' => 'Moneda Local',
                    'Source' => null,
                ],
            ];

            $body = [
                'Id' => 5436,
                'Skip' => $skip,
                'Take' => $take,
                'Sort' => ' ',
                'FilterCriterias' => json_encode($filterCriterias),
                'Params' => json_encode([
                    'TabID' => '1609',
                    'DueType' => '-1',
                    'pTabID' => '538',
                    'rReport' => '1',
                ]),
                'GetTotalCount' => $total === null,
                'GridOrderCriteria' => null,
                'AddOns' => null,
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(600)
                ->connectTimeout(30)
                ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

            if (!$response->successful()) {
                throw new \Exception(
                    'Error consultando documentos del proveedor: ' . $response->body()
                );
            }

            if ($total === null) {
                $total = (int) $response->json('totalCount');
            }

            $page = $response->json('data.Value.Table') ?? [];
            $rows = array_merge($rows, $page);

            $skip += $take;
        } while ($skip < $total);

        return $rows;
    }

    private function purchases_documents(string $token, int $accountId)
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        $fechaInicio = now()->subYear(2);
        $fechaFin = now();

        $source = collect(range(2016, $fechaFin->year))
            ->map(fn ($anio) => [
                'id' => $anio,
                'StartDate' => "{$anio}0101",
                'EndDate' => "{$anio}1231",
            ])
            ->values()
            ->toArray();

        do {
            $filterCriterias = [
                [
                    'Field' => '_vTypeTransaction',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => ['0'],
                    'ValueUI' => 'Compra',
                    'Source' => 'PurchasesTransactionEnum',
                ],
                [
                    'Field' => '_vProvider',
                    'FilterType' => 68,
                    'OperatorType' => 0,
                    'Value' => [$accountId, false, 'AccountID'],
                    'ValueUI' => '',
                    'Source' => 'Account',
                ],
                [
                    'Field' => '_vDocDate',
                    'FilterType' => 76,
                    'OperatorType' => 0,
                    'Value' => [
                        $fechaInicio->format('Ymd'),
                        $fechaFin->format('Ymd'),
                    ],
                    'ValueUI' => $fechaInicio->format('Y/m/d')
                        . ' - '
                        . $fechaFin->format('Y/m/d'),
                    'Source' => $source,
                ],
                [
                    'Field' => '_vUser',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '12',
                ],
                [
                    'Field' => '_vProviderInvoice',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '64',
                ],
                [
                    'Field' => '_vESiigoStatus',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => ['-1'],
                    'ValueUI' => '',
                    'Source' => 'DianStateFilterEnum',
                ],
            ];

            $body = [
                'Id' => 5451,
                'Skip' => $skip,
                'Take' => $take,
                'Sort' => ' ',
                'FilterCriterias' => json_encode($filterCriterias),
                'Params' => json_encode([
                    'TabID' => '1408',
                ]),
                'GetTotalCount' => $total === null,
                'GridOrderCriteria' => null,
                'AddOns' => [
                    [
                        'name' => 'POS Web',
                        'state' => true,
                        'tenantId' => '0x00000000000000000000000000605286',
                        'type' => 1,
                        'module' => 5,
                        'dateActive' => '08/05/2026 10:02:54.011',
                        'posActiveCashiers' => [
                            'baseCashiers' => 1,
                            'aditionalCashiers' => 27,
                        ],
                        'documentBase' => 0,
                        'readOnly' => null,
                        'subState' => 1,
                        'updateType' => 1,
                        'complements' => null,
                        'payrollComplements' => null,
                    ],
                ],
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(600)
                ->connectTimeout(30)
                ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

            if (!$response->successful()) {
                throw new \Exception(
                    'Error consultando documentos de compra: ' . $response->body()
                );
            }

            if ($total === null) {
                $total = (int) $response->json('totalCount');
            }

            $page = $response->json('data.Value.Table') ?? [];

            $rows = array_merge($rows, $page);

            $skip += $take;

        } while ($skip < $total);

        return $rows;
    }

    private function payments_documents(string $token, int $accountId)
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        $fechaInicio = now()->subMonths(2);
        $fechaFin = now();

        $source = collect(range(2015, $fechaFin->year))
            ->map(fn ($anio) => [
                'id' => $anio,
                'StartDate' => "{$anio}0101",
                'EndDate' => "{$anio}1231",
            ])
            ->values()
            ->toArray();

        do {
            $filterCriterias = [
                [
                    'Field' => '_vTypeTransaction',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => ['1'],
                    'ValueUI' => 'Pagos',
                    'Source' => 'PurchasesTransactionEnum',
                ],
                [
                    'Field' => '_vProvider',
                    'FilterType' => 68,
                    'OperatorType' => 0,
                    'Value' => [$accountId, false, 'AccountID'],
                    'ValueUI' => '',
                    'Source' => 'Account',
                ],
                [
                    'Field' => '_vDocDate',
                    'FilterType' => 76,
                    'OperatorType' => 0,
                    'Value' => [
                        $fechaInicio->format('Ymd'),
                        $fechaFin->format('Ymd'),
                    ],
                    'ValueUI' => $fechaInicio->format('Y/m/d')
                        . ' - '
                        . $fechaFin->format('Y/m/d'),
                    'Source' => $source,
                ],
                [
                    'Field' => '_vUser',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '12',
                ],
                [
                    'Field' => '_vProviderInvoice',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '64',
                ],
                [
                    'Field' => '_vESiigoStatus',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => ['-1'],
                    'ValueUI' => '',
                    'Source' => 'DianStateFilterEnum',
                ],
            ];

            $body = [
                'Id' => 5451,
                'Skip' => $skip,
                'Take' => $take,
                'Sort' => ' ',
                'FilterCriterias' => json_encode($filterCriterias),
                'Params' => json_encode([
                    'TabID' => '1408',
                ]),
                'GetTotalCount' => $total === null,
                'GridOrderCriteria' => null,
                'AddOns' => [
                    [
                        'name' => 'POS Web',
                        'state' => true,
                        'tenantId' => '0x00000000000000000000000000605286',
                        'type' => 1,
                        'module' => 5,
                        'dateActive' => '08/05/2026 10:02:54.011',
                        'posActiveCashiers' => [
                            'baseCashiers' => 1,
                            'aditionalCashiers' => 27,
                        ],
                        'documentBase' => 0,
                        'readOnly' => null,
                        'subState' => 1,
                        'updateType' => 1,
                        'complements' => null,
                        'payrollComplements' => null,
                    ],
                ],
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(600)
                ->connectTimeout(30)
                ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

            if (!$response->successful()) {
                throw new \Exception(
                    'Error consultando pagos: ' . $response->body()
                );
            }

            if ($total === null) {
                $total = (int) $response->json('totalCount');
            }

            $page = $response->json('data.Value.Table') ?? [];

            $rows = array_merge($rows, $page);

            $skip += $take;

        } while ($skip < $total);

        return $rows;
    }

    private function purchase_entry(string $token, int $accountId, int $acEntryId)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->get('https://services.siigo.com/document/api/v2/cards-view/CardsInfo', [
                'ACEntryID' => $acEntryId,
                'AccountID' => $accountId,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Error consultando detalle de compra '. $acEntryId . ': '. $response->body());
        }

        $data = $response->json();

        $data = $data['accounts'];

        return [
            'quotationID' => $data['quotationID'] ?? null,
            'docName' => $data['docName'] ?? '',
        ];
    }

    private function purchase_entry_detail(string $token, int $acEntryId)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->get('https://services.siigo.com/ACEntryApi/api/v2/Purchase/GetItem', [
                'id' => $acEntryId,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Error consultando detalle de compra '. $acEntryId . ': '. $response->body());
        }

        $data = $response->json();

        $items = collect($data['Items'] ?? []);

        return [
            'DocDate' => isset($data['Entry']['DocDate']) ? Carbon::createFromFormat('Ymd', $data['Entry']['DocDate'])->format('d/m/Y') : null,
            'Observations' => $data['Entry']['Observations'] ?? '',
            'WarehouseCodes' => $items
                ->pluck('WarehouseCode')
                ->filter(fn ($warehouseCode) => $warehouseCode !== null)
                ->unique()
                ->values()
                ->all(),
            'Quantity' => $items
                ->sum(fn ($item) => (float) ($item['Quantity'] ?? 0)),
        ];
    }

    private function payment_entry(string $token, int $acEntryId): string
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->get(
                'https://services.siigo.com/document/api/v2/document-view/GetHtmlView',
                [
                    'ACEntryID' => $acEntryId,
                    'IsBase64' => 'false',
                ]
            );

        if (!$response->successful()) {
            throw new \Exception('Error consultando detalle de pago ' . $acEntryId . ': ' . $response->body());
        }

        $data = $response->json();

        return $data['strHTML'] ?? '';
    }

    private function warehouses(string $token): array
    {
        $response = Http::retry(5, 10000)->withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id'    => 'consultadeFacturas',
        ])->get("https://api.siigo.com/v1/warehouses");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        return $data;
    }

    private function type_receipt(string $token, string|int $erpDocumentTypeId)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->get('https://services.siigo.com/ACGeneralApi/api/v1/JournalEntryType/GetById', [
                'ERPDocumentTypeId' => $erpDocumentTypeId,
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando tipos de recibo de pago: ' . $response->body()
            );
        }

        $data = $response->json();

        return $data;
    }

    private function bank_accounts(string $token)
    {
        $numRecordView = 0;
        $resultados = [];

        do {
            $body = [
                'type' => 1,
                'browserID' => '11',
                'query' => '',
                'filter' => 'IsActive = 1 AND IsTransactional = 1 AND InUse = 1 AND
                    AcAccountType IN(14)
                    AND IsSystem = 0
                    AND (DueType IS NULL OR DueType = 0)
                    AND Type <> 0',
                'tags' => (object) [],
                'viewAll' => true,
                'numRecordView' => $numRecordView,
            ];

            $response = Http::retry(3, 3000)
                ->withToken($token)
                ->asJson()
                ->post('https://services.siigo.com/catalog/api/v1/Autocomplete/GetData', $body);

            if (!$response->successful()) {
                throw new \Exception(
                    'Error consultando cuentas: ' . $response->body()
                );
            }

            $data = $response->json();

            if (is_string($data)) {
                if ($data === '') break;
                $data = json_decode($data, true);
            }

            if (empty($data)) break;
            $resultados = array_merge($resultados, $data);
            $numRecordView += 10;

        } while (true);

        return $resultados;
    }

    private function type_documents(string $token)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->get('https://services.siigo.com/ACGeneralApi/api/v1/JournalEntryType/GetActiveJournalEntryAutomaticEnumTypes');

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando tipos de documentos: ' . $response->body()
            );
        }

        $data = json_decode($response->json(), true );

        return collect($data)->whereIn('ERPDocCode', ['21'])->values();
    }

    private function search_account(string $token, string $query)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->connectTimeout(30)
            ->post('https://services.siigo.com/catalog/api/v1/Autocomplete/GetData', [
                'browserID' => '3',
                'filter' => '(IsTransactional = 1 AND IsActive = 1)',
                'query' => $query,
                'tags' => (object) [],
                'type' => 1
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando la cuenta contable: ' . $response->body()
            );
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

        $data = json_decode($data, true);

        return collect($data)->firstWhere('Code', $query);
    }

    private function search_list(string $token, string $ACAccountCode, string $AccountCode, array $docs)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->connectTimeout(30)
            ->post('https://services.siigo.com/catalog/api/v1/Autocomplete/GetData', [
                'browserID' => '9',
                'filter' => "(Balance <> 0 or ForeignBalance <> 0) AND ACAccountCode = $ACAccountCode AND AccountCode = $AccountCode AND ISNULL(MoneyCode, '') = ''",
                'query' => "",
                'tags' => (object) [],
                'type' => 1
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando la cuenta contable: ' . $response->body()
            );
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

        $data = json_decode($data, true);

        return collect($data)
            ->filter(function ($item) use ($docs) {
                $documento = $item['DuePrefix'] . '-' . $item['DueConsecutive'];
                return in_array($documento, $docs);
            })
            ->values()
            ->toArray();
    }
}

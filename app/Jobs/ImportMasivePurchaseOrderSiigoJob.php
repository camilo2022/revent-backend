<?php

namespace App\Jobs;

use App\Mail\MasivePurchaseOrderProviderSiigo;
use App\Mail\MasivePurchaseOrderSiigo;
use App\Services\SiigoInventoryService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\DomCrawler\Crawler;

class ImportMasivePurchaseOrderSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DISK = 'public';
    private const BASE_PATH = 'products';

    /**
     * El proceso hace varias llamadas HTTP a Siigo + sleep(30) entre
     * órdenes, así que le damos bastante margen.
     */
    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        public Collection $order,
        public string $email,
        public string $referencia,
    ) {}

    public function handle(): void
    {
        $ordenes_compra = [];
        $errors = [];

        $this->referencia = $this->sanitize_referencia($this->referencia);
        $path = self::BASE_PATH . "/{$this->referencia}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            $this->notificarResultado([[
                'Row' => 'FOTO',
                'Error' => 'La(s) foto(s) es obligatorio.',
            ]]);
            return;
        }

        $files = $this->obtener_archivos($this->referencia);

        $config = $this->order['orden_compra'][0]->toArray();

        $cookie = $config['cookie'];

        if (empty($cookie)) {
            $this->notificarResultado([[
                'Row' => 'COOKIE',
                'Error' => 'La cookie es obligatorio',
            ]]);
            return;
        }

        $token = $this->extraer_token($cookie);

        $purchase_order_type_id = $config['tipo'];

        [$user, $validate] = $this->obtener_datos_usuario($token);
        if (!empty($validate)) {
            $this->notificarResultado($validate);
            return;
        }

        if (empty($purchase_order_type_id)) {
            $this->notificarResultado([[
                'Row' => 'TIPO',
                'Error' => 'El tipo de orden de compra es obligatorio',
            ]]);
            return;
        }

        $purchase_order_type = $this->purchase_order_type($token, $cookie, $purchase_order_type_id);

        if (empty($purchase_order_type)) {
            $this->notificarResultado([[
                'Row' => 'TIPO',
                'Error' => 'El tipo de orden de compra no es valido',
            ]]);
            return;
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

        $cost_center = [];

        if ($purchase_order_type->UseCostCenter) {
            if (empty($config['centro_costo'])) {
                $errors[] = [
                    'Row' => 'CENTRO COSTO',
                    'Error' => 'El centro de costo es obligatorio',
                ];
            }

            $cost_centers = collect($this->cost_centers())->keyBy('id')->all();

            if (!isset($cost_centers[$config['centro_costo']])) {
                $errors[] = [
                    'Row' => 'CENTRO COSTO',
                    'Error' => 'El centro de costo no es valido',
                ];
            } else {
                $cost_center = $cost_centers[$config['centro_costo']];
            }
        }

        $list_taxes = $this->list_taxes($token, $cookie);

        if ($purchase_order_type->IsReteIva) {
            if (!isset($config['rete_iva']) || $config['rete_iva'] === '') {
                $errors[] = [
                    'Row' => 'RETE IVA',
                    'Error' => 'El rete iva es obligatorio',
                ];
            } elseif (!collect($list_taxes['rete_iva'] ?? [])->contains('Id', $config['rete_iva'])) {
                $errors[] = [
                    'Row' => 'RETE IVA',
                    'Error' => 'El rete iva no es valido',
                ];
            }
        }

        if ($purchase_order_type->IsReteIca) {
            if (!isset($config['rete_ica']) || $config['rete_ica'] === '') {
                $errors[] = [
                    'Row' => 'RETE ICA',
                    'Error' => 'El rete ica es obligatorio',
                ];
            } elseif (!collect($list_taxes['rete_ica'] ?? [])->contains('Id', $config['rete_ica'])) {
                $errors[] = [
                    'Row' => 'RETE ICA',
                    'Error' => 'El rete ica no es valido',
                ];
            }
        }

        if (!empty($errors)) {
            $this->notificarResultado($errors);
            return;
        }

        if (empty($config['proveedor'])) {
            $this->notificarResultado([[
                'Row' => 'PROVEEDOR',
                'Error' => 'El proveedor es obligatorio',
            ]]);
            return;
        }

        $search_providers = $this->search_providers($token, $cookie, $config['proveedor']);

        if ($search_providers->count() > 1) {
            $this->notificarResultado([[
                'Row' => 'PROVEEDOR',
                'Error' => 'El proveedor no es valido. Multiples registros de busqueda',
            ]]);
            return;
        } elseif ($search_providers->isEmpty()) {
            $this->notificarResultado([[
                'Row' => 'PROVEEDOR',
                'Error' => 'El proveedor no es valido',
            ]]);
            return;
        }

        $search_providers = $search_providers->first();
        $provider = $this->search_provider($token, $cookie, $search_providers['ID']);

        $warehouses = $this->warehouses();
        $warehouses = collect($warehouses)->keyBy('id')->all();

        $fecha = Carbon::createFromFormat('Y-m-d', $config['fecha'])->format('Ymd');

        [$orden_compra_detalles, $errors] = $this->validar_detalles($token, $cookie, $this->order['orden_compra_detalles']->toArray(), $warehouses, $fecha, $list_taxes['imp_cargo'], $list_taxes['imp_retencion'], $user);

        if (!empty($errors)) {
            $this->notificarResultado($errors);
            return;
        }

        $orden_compra_detalles = collect($orden_compra_detalles)->groupBy('Warehouse_Id');

        foreach ($orden_compra_detalles as $warehouse_id => $detalles) {
            $warehouse = $warehouses[$warehouse_id];

            $detalles_agrupados = $detalles->groupBy(function ($detalle) {
                if ($detalle['TaxDiscount_Id'] == -1 && $detalle['TaxAdd_Id'] == -1) return 'REMISION';

                return 'IVA';
            });

            foreach ($detalles_agrupados as $tipo => $detalles_tipo) {

                $body = $this->separar_ordenes_compra($config, $user, $purchase_order_type, $list_taxes['rete_iva'], $list_taxes['rete_ica'], $provider, $cost_center, $fecha, $detalles_tipo, $warehouse, $tipo);

                [$orden_compra, $validate] = $this->orden_compra($token, $cookie, $body, $warehouse, $tipo);

                if (!empty($validate)) {
                    $errors = array_merge($errors, $validate);
                    continue;
                }

                $info = $this->consultar_orden_compra($token, $cookie, $orden_compra['documento_id']);

                $ordenes_compra[] = [
                    'bodega' => $warehouse,
                    'tipo' => $tipo,
                    'documento' => $info['documento'],
                    'url' => $info['url'],
                ];

                sleep(30);
            }
        }

        Mail::to($this->email)->cc('tecnologia@revent.com.co')->send(new MasivePurchaseOrderProviderSiigo($ordenes_compra, $files, $this->referencia, $provider));
        $this->notificarResultado($errors, $ordenes_compra);
    }

    private function notificarResultado(array $errors, array $ordenes_compra = []): void
    {
        Mail::to(['tecnologia@revent.com.co'])->send(new MasivePurchaseOrderSiigo($ordenes_compra, $errors));
    }

    private function obtener_archivos(string $referencia): Collection
    {
        $path = self::BASE_PATH . "/{$referencia}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            return collect();
        }

        return collect(Storage::disk(self::DISK)->files($path))
            ->map(fn ($file) => [
                'name' => basename($file),
                'url' => Storage::disk(self::DISK)->url($file),
            ])
            ->values();
    }

    private function consultar_orden_compra(string $token, string $cookie, int|string $erp_document_id): array
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->get('https://monolithprod.siigo.com/REVENTCALZADOSAS/Default.aspx', [
                'TabID' => 1671,
                'ERPDocumentID' => $erp_document_id,
                'pTabID' => 1408,
            ]);

        if (!$response->successful()) {
            return ['documento' => null, 'url' => null];
        }

        $crawler = new Crawler($response->body());

        $documento = null;
        $titleSpan = $crawler->filter('#Default_ucControlPane0_ContainerTitle');
        if ($titleSpan->count()) {
            $documento = trim(Str::after($titleSpan->text(), ':'));
        }

        $url = null;
        $btnCopyUrl = $crawler->filter('#Default_ucControlPane0_ctl00_btnCopyUrl');
        if ($btnCopyUrl->count()) {
            $onclick = $btnCopyUrl->attr('onclick');

            preg_match('/setClipboardText\(["\'](.*?)["\']\)/', $onclick, $matches);

            if (isset($matches[1])) {
                $url = html_entity_decode($matches[1]);
            }
        }

        return [
            'documento' => $documento,
            'url' => $url,
        ];
    }

    private function orden_compra(string $token, string $cookie, array $body, array $warehouse, string $tipo)
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->asMultipart()
            ->post('https://monolithprod.siigo.com/REVENTCALZADOSAS/Components/ERP/Business/ERPDocHandler.ashx', [
                    ['name' => 'Process', 'contents' => $body['Process']],
                    ['name' => 'ERPDocType', 'contents' => $body['ERPDocType']],
                    ['name' => 'ERPDocGeneral', 'contents' => json_encode($body['ERPDocGeneral'])],
                    ['name' => 'lstERPDocItem', 'contents' => json_encode($body['lstERPDocItem'])],
                    ['name' => 'ERPDocumentTotal', 'contents' => json_encode($body['ERPDocumentTotal'])],
                    ['name' => 'ERPDocumentDue', 'contents' => $body['ERPDocumentDue']],
                    ['name' => 'ERPDocumentPayment', 'contents' => $body['ERPDocumentPayment']],
                    ['name' => 'AdvanceValue', 'contents' => $body['AdvanceValue']],
                    ['name' => 'PaymentTotal', 'contents' => $body['PaymentTotal']],
                    ['name' => 'ERPDocumentConfigModel', 'contents' => $body['ERPDocumentConfigModel']],
                    ['name' => 'JSONERPDocType', 'contents' => json_encode($body['JSONERPDocType'])],
                ]
            );

        if (!$response->successful()) {
            $validate = [
                [
                    'Row'   => "ERROR SIIGO - ORDEN DE COMPRA: {$warehouse['id']} - {$warehouse['name']} {$tipo}",
                    'Error' => "No se pudo realizar la carga debido a problemas con Siigo. Los detalles de esta bodega y tipo ({$tipo}) deben ser separados para realizar nuevamente la carga. Error de Siigo: " . $response->body(),
                ]
            ];

            return [[], $validate];
        }

        $data = $this->parse_siigo_response($response->body());

        if (empty($data['success']) || $data['success'] !== true) {
            $validate = [
                [
                    'Row'   => "ADVERTENCIA SIIGO - ORDEN DE COMPRA: {$warehouse['id']} - {$warehouse['name']} {$tipo}",
                    'Error' => "Es posible que la orden de compra se haya creado correctamente en Siigo, pero la respuesta recibida no fue la esperada. Verifique directamente en Siigo si el documento fue creado antes de intentar realizar nuevamente la carga. Respuesta de Siigo: " . ($data['msg'] ?? 'Error desconocido al crear la orden de compra'),
                ]
            ];

            return [[], $validate];
        }

        return [['documento_id' => $data['erpDocumentID'] ?? null, 'url' => 'https://monolithprod.siigo.com' . $data['url']], []];
    }

    private function parse_siigo_response(string $body): array
    {
        // Convierte { success:true, url: 'algo', erpDocumentID:2515553 } en JSON válido
        $json = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $body);
        $json = preg_replace("/:\s*'([^']*)'/", ': "$1"', $json);

        $data = json_decode($json, true);

        return $data ?? [];
    }

    private function separar_ordenes_compra(array $config, array $user, object $purchase_order_type, array $rete_iva, array $rete_ica, array $provider, array $cost_center, string $doc_date, Collection $detalles, array $warehouse, string $tipo)
    {
        $total_base = $detalles->sum('BaseValue');
        $vat_total_value = $detalles->sum('TaxAdd_Value');
        $tax_disc_total_value = $detalles->sum('TaxDiscount_Value');

        $erp_document_total = collect();

        $detalles->filter(function ($detalle) {
                return isset($detalle['TaxAdd_Id'])
                    && (int) $detalle['TaxAdd_Id'] > 0
                    && (float) ($detalle['TaxAdd_Value'] ?? 0) > 0;
            })
            ->groupBy('TaxAdd_Id')
            ->each(function ($items, $tax_id) use ($erp_document_total) {
                $erp_document_total->push([
                    'Id' => (int) $tax_id,
                    'Value' => $items->sum('TaxAdd_Value'),
                    'TotalBase' => $items->sum('BaseValue'),
                ]);
            });

        $detalles->filter(function ($detalle) {
                return isset($detalle['TaxDiscount_Id'])
                    && (int) $detalle['TaxDiscount_Id'] > 0
                    && (float) ($detalle['TaxDiscount_Value'] ?? 0) > 0;
            })
            ->groupBy('TaxDiscount_Id')
            ->each(function ($items, $tax_id) use ($erp_document_total) {

                $erp_document_total->push([
                    'Id' => (int) $tax_id,
                    'Value' => $items->sum('TaxDiscount_Value'),
                    'TotalBase' => $items->sum('BaseValue'),
                ]);
            });

        $erp_document_total = $erp_document_total->values()->all();

        $ret_vat_total_code = (int) ($config['rete_iva'] ?? -1);
        $ret_vat_total_value = 0;
        $ret_vat_total_percentage = -1;
        $ret_vat_name = '';
        $ret_vat_base_value = 0;

        if ($purchase_order_type->IsReteIva && $ret_vat_total_code != -1) {
            $rete_iva_selected = collect($rete_iva)->firstWhere('Id', $ret_vat_total_code);
            if ($rete_iva_selected) {
                $ret_vat_total_percentage = (float) $rete_iva_selected['Value'];
                $ret_vat_name = $rete_iva_selected['Name'];
                $ret_vat_base_value = $vat_total_value;
                $ret_vat_total_value = round($ret_vat_base_value * ($ret_vat_total_percentage / 100), 2);
            }
        }

        $ret_ica_total_code = (int) ($config['rete_ica'] ?? -1);
        $ret_ica_total_value = 0;
        $ret_ica_total_percentage = -1;
        $ret_ica_name = '';
        $ret_ica_base_value = 0;

        if ($purchase_order_type->IsReteIca && $ret_ica_total_code != -1) {
            $rete_ica_selected = collect($rete_ica)->firstWhere('Id', $ret_ica_total_code);
            if ($rete_ica_selected) {
                $ret_ica_total_percentage = (float) $rete_ica_selected['Value'];
                $ret_ica_name = $rete_ica_selected['Name'];
                $ret_ica_base_value = $total_base;
                $ret_ica_total_value = round($ret_ica_base_value * ($ret_ica_total_percentage / 1000), 2);
            }
        }

        $total_value = $total_base + $vat_total_value - $tax_disc_total_value - $ret_ica_total_value - $ret_vat_total_value;

        $observaciones = "DIRIGIDO A: {$warehouse['id']} - {$warehouse['name']}. TIPO: {$tipo}. " . ($config['observaciones'] ?? '');

        return [
            "Process" => 1,
            "ERPDocType" => $purchase_order_type->ERPType,
            "ERPDocGeneral" => [
                "ERPDocumentID" => -1,
                "ForeignMoneyCode" => "",
                "ApplyForAllDay" => false,
                "GeneratePdf" => false,
                "lngLastAccount" => -1,
                "WFInstanceID" => -1,
                "ERPDocDate" => $doc_date,
                "Number" => -1,
                "AccountID" => $provider['ID'],
                "ContactID" => $provider['contactID'],
                "SalesmanID" => $user['id'],
                "ERPDocumentType" => $purchase_order_type->ERPDocumentType,
                "ERPDocClass" => $purchase_order_type->ERPDocClass,
                "ERPDocCode" => $purchase_order_type->ERPDocCode,
                "ConsumptionTaxTotalValue" => 0,
                "TaxDiscTotalValue" => $tax_disc_total_value,
                "VATTotalValue" => $vat_total_value,
                "TotalValue" => $total_value,
                "TotalBase" => $total_base,
                "TotalBaseAIU" => 0,
                "RetICAName" => $ret_ica_name,
                "RetICABaseValue" => $ret_ica_base_value,
                "RetICATotalCode" => $ret_ica_total_code,
                "RetICATotalValue" => $ret_ica_total_value,
                "RetICATotaPercentage" => $ret_ica_total_percentage,
                "RetVATTotalCode" => $ret_vat_total_code,
                "RetVATTotalValue" => $ret_vat_total_value,
                "RetVATTotalPercentage" => $ret_vat_total_percentage,
                "RetVATName" => $ret_vat_name,
                "RetVATBaseValue" => $ret_vat_base_value,
                "AttachmentsFSItemsGUID" => $purchase_order_type->AttachmentsFSItemsGUID,
                "TotalDiscountPercentage" => 0,
                "TotalDiscountValue" => 0,
                "Header" => $purchase_order_type->Header,
                "CommercialConditions" => $purchase_order_type->CommercialCoditions,
                "ERPDocumentCode" => -1,
                "Observations" => $observaciones,
                "IsAllowDecimals" => $purchase_order_type->AllowDecimals,
                "CostCenterCode" => $purchase_order_type->UseCostCenter ? $cost_center['id'] : $purchase_order_type->CostCenterDefaultCode,
                "ConfigInitial" => json_encode(["BaseAIU" => "False"]),
                "ConsumptionValue" => 0,
                "AllowTaxImpoByValue" => $purchase_order_type->AllowTaxImpoByValue,
                "TaxIncluded" => $purchase_order_type->TaxIncluded,
                "ExternalERPDocName" => null,
                "ERPDocType" => [
                    "UseDIANVadility" => $purchase_order_type->UseDIANVadility,
                    "ERPDocumentType" => $purchase_order_type->ERPDocumentType,
                    "ERPType" => $purchase_order_type->ERPType,
                    "IsPercentage" => $purchase_order_type->IsPercentage,
                    "IsReteIva" => $purchase_order_type->IsReteIva,
                    "IsReteIca" => $purchase_order_type->IsReteIca,
                    "Atachments" => $purchase_order_type->Atachments,
                    "IsSalesBySalesman" => $purchase_order_type->IsSalesBySalesman,
                    "CostCenterDefaultName" => $purchase_order_type->CostCenterDefaultName,
                    "UseWarehouse" => $purchase_order_type->UseWarehouse,
                    "LastWarehouseCode" => $purchase_order_type->LastWarehouseCode,
                    "UseUnitValue" => $purchase_order_type->UseUnitValue,
                    "EnableTwoTaxes" => $purchase_order_type->EnableTwoTaxes,
                    "ShowAIU" => $purchase_order_type->ShowAIU,
                    "UseQuantity" => $purchase_order_type->UseQuantity,
                    "IsItemsByAccount" => $purchase_order_type->IsItemsByAccount,
                    "AllowTaxImpoByValue" => $purchase_order_type->AllowTaxImpoByValue,
                    "TaxIncluded" => $purchase_order_type->TaxIncluded,
                    "CreatedAsProviderByItem" => $purchase_order_type->CreatedAsProviderByItem,
                    "AllowValueWithChargeTaxes" => $purchase_order_type->AllowValueWithChargeTaxes,
                    "NextNumber" => $purchase_order_type->NextNumber,
                    "AllowTaxAdd2" => $purchase_order_type->AllowTaxAdd2,
                    "TaxAdd2Name" => $purchase_order_type->TaxAdd2Name,
                    "ERPDocumentTypeID" => $purchase_order_type->ERPDocumentTypeID,
                    "Name" => $purchase_order_type->Name,
                    "ERPDocClass" => $purchase_order_type->ERPDocClass,
                    "ERPDocCode" => $purchase_order_type->ERPDocCode,
                    "NumberInitial" => $purchase_order_type->NumberInitial,
                    "DIANPrefix" => $purchase_order_type->DIANPrefix,
                    "ERPDocument" => $purchase_order_type->ERPDocument,
                    "DIANResolution" => $purchase_order_type->DIANResolution,
                    "DIANAuthorizationDate" => $purchase_order_type->DIANAuthorizationDate,
                    "DIANNumberStart" => $purchase_order_type->DIANNumberStart,
                    "DIANNumberEnd" => $purchase_order_type->DIANNumberEnd,
                    "DIANVadility" => $purchase_order_type->DIANVadility,
                    "SubjectEMail" => $purchase_order_type->SubjectEMail,
                    "BodyEmail" => $purchase_order_type->BodyEmail,
                    "IsAutomaticSignature" => $purchase_order_type->IsAutomaticSignature,
                    "CreatedByDate" => $purchase_order_type->CreatedByDate,
                    "CreatedByUser" => $purchase_order_type->CreatedByUser,
                    "UpdatedByUser" => $purchase_order_type->UpdatedByUser,
                    "UpdatedByDate" => $purchase_order_type->UpdatedByDate,
                    "InternalDescription" => $purchase_order_type->InternalDescription,
                    "IsAutomaticEnum" => $purchase_order_type->IsAutomaticEnum,
                    "Consecutive" => $purchase_order_type->Consecutive,
                    "IsOnlinePayment" => $purchase_order_type->IsOnlinePayment,
                    "IsRoundNumber" => $purchase_order_type->IsRoundNumber,
                    "IsDiscountPercentaje" => $purchase_order_type->IsDiscountPercentaje,
                    "IsDiscountValue" => $purchase_order_type->IsDiscountValue,
                    "Comments" => $purchase_order_type->Comments,
                    "IsNew" => $purchase_order_type->IsNew,
                    "UseDocumentSupport" => $purchase_order_type->UseDocumentSupport,
                    "Prefix" => $purchase_order_type->Prefix,
                    "PrintMsg" => $purchase_order_type->PrintMsg,
                    "CreateTask" => $purchase_order_type->CreateTask,
                    "TemplateName" => $purchase_order_type->TemplateName,
                    "TemplatePath" => $purchase_order_type->TemplatePath,
                    "ViewSelectedColumns" => $purchase_order_type->ViewSelectedColumns,
                    "LayoutSelectedColumns" => $purchase_order_type->LayoutSelectedColumns,
                    "SignatureEmail" => $purchase_order_type->SignatureEmail,
                    "AllowRetVAT" => $purchase_order_type->AllowRetVAT,
                    "AllowRetICA" => $purchase_order_type->AllowRetICA,
                    "IsActive" => $purchase_order_type->IsActive,
                    "Header" => $purchase_order_type->Header,
                    "CommercialCoditions" => $purchase_order_type->CommercialCoditions,
                    "TopContent" => $purchase_order_type->TopContent,
                    "BottomContent" => $purchase_order_type->BottomContent,
                    "LeftContent" => $purchase_order_type->LeftContent,
                    "RightContent" => $purchase_order_type->RightContent,
                    "FormatName" => $purchase_order_type->FormatName,
                    "AttachmentsFSItemsGUID" => $purchase_order_type->AttachmentsFSItemsGUID,
                    "AllowDecimals" => $purchase_order_type->AllowDecimals,
                    "AllowSalesBySalesman" => $purchase_order_type->AllowSalesBySalesman,
                    "AllowSalesByAccount" => $purchase_order_type->AllowSalesByAccount,
                    "AllowOrderReference" => $purchase_order_type->AllowOrderReference,
                    "AllowOrderDelivery" => $purchase_order_type->AllowOrderDelivery,
                    "UseCostCenter" => $purchase_order_type->UseCostCenter,
                    "CostCenterMandatory" => $purchase_order_type->CostCenterMandatory,
                    "CostCenterDefaultCode" => $purchase_order_type->CostCenterDefaultCode,
                    "IsShowResolutionDIAN" => $purchase_order_type->IsShowResolutionDIAN,
                    "CLDocumentTypeCode" => $purchase_order_type->CLDocumentTypeCode,
                    "TaxImpoByValue" => $purchase_order_type->TaxImpoByValue,
                    "AssociatedType" => $purchase_order_type->AssociatedType,
                    "Address" => $purchase_order_type->Address,
                    "CityCode" => $purchase_order_type->CityCode,
                    "Phone" => $purchase_order_type->Phone,
                    "AccountCode" => $purchase_order_type->AccountCode,
                    "ContactCode" => $purchase_order_type->ContactCode,
                    "ProductWarehouseCode" => $purchase_order_type->ProductWarehouseCode,
                    "PriceListCode" => $purchase_order_type->PriceListCode,
                    "AllowCards" => $purchase_order_type->AllowCards,
                    "AllowOthers" => $purchase_order_type->AllowOthers,
                    "AllowCredit" => $purchase_order_type->AllowCredit,
                    "AllowChangeSalesman" => $purchase_order_type->AllowChangeSalesman,
                    "AllowDiscount" => $purchase_order_type->AllowDiscount,
                    "AllowAllPriceList" => $purchase_order_type->AllowAllPriceList,
                    "AllowChangePrice" => $purchase_order_type->AllowChangePrice,
                    "AllowPreliminary" => $purchase_order_type->AllowPreliminary,
                    "AllowAIU" => $purchase_order_type->AllowAIU,
                    "TaxAdd2Code" => $purchase_order_type->TaxAdd2Code,
                    "AllowAdvance" => $purchase_order_type->AllowAdvance,
                    "ACAccountCodeAdvance" => $purchase_order_type->ACAccountCodeAdvance,
                    "ACAccountCode" => $purchase_order_type->ACAccountCode,
                    "ApplyAccountingBook" => $purchase_order_type->ApplyAccountingBook,
                    "ElectronicInvoiceType" => $purchase_order_type->ElectronicInvoiceType,
                    "ElectronicInvoiceKey" => $purchase_order_type->ElectronicInvoiceKey,
                    "CreditACPaymentMeanCode" => $purchase_order_type->CreditACPaymentMeanCode,
                    "ERPDocumentTypeJournalEntryCode" => $purchase_order_type->ERPDocumentTypeJournalEntryCode,
                    "MoneyIncomeACAccountCode" => $purchase_order_type->MoneyIncomeACAccountCode,
                    "MoneyWithdrawalACAccountCode" => $purchase_order_type->MoneyWithdrawalACAccountCode,
                    "ESiigoTestEntryType" => $purchase_order_type->ESiigoTestEntryType,
                    "ESiigoTestIncludeCufeQR" => $purchase_order_type->ESiigoTestIncludeCufeQR,
                    "AllowReprintInvoices" => $purchase_order_type->AllowReprintInvoices,
                    "ShowValues" => $purchase_order_type->ShowValues,
                    "UseCodeBarReader" => $purchase_order_type->UseCodeBarReader,
                    "DIANEndDate" => $purchase_order_type->DIANEndDate,
                    "AllowSelfWithholdingTax" => $purchase_order_type->AllowSelfWithholdingTax,
                    "SelfWithholdingTaxCode" => $purchase_order_type->SelfWithholdingTaxCode,
                    "SelfWithholdingLowerLimit" => $purchase_order_type->SelfWithholdingLowerLimit,
                    "AllowMultipleRemittance" => $purchase_order_type->AllowMultipleRemittance,
                    "AllowDocumentReference" => $purchase_order_type->AllowDocumentReference,
                    "Establishment" => $purchase_order_type->Establishment,
                    "EmissionPoint" => $purchase_order_type->EmissionPoint,
                    "ACAccountCodeGift" => $purchase_order_type->ACAccountCodeGift,
                    "TypeRetention" => $purchase_order_type->TypeRetention,
                    "ERPDocumentTypeCreditNoteCode" => $purchase_order_type->ERPDocumentTypeCreditNoteCode,
                    "CanAnnullInvoice" => $purchase_order_type->CanAnnullInvoice,
                    "CanMakeCreditNote" => $purchase_order_type->CanMakeCreditNote,
                    "ERPDocumentAllowDecimals" => $purchase_order_type->ERPDocumentAllowDecimals,
                    "NotifyWhenNotHaveStock" => $purchase_order_type->NotifyWhenNotHaveStock,
                    "AllowISR" => $purchase_order_type->AllowISR,
                    "NExterior" => $purchase_order_type->NExterior,
                    "NInside" => $purchase_order_type->NInside,
                    "Suburb" => $purchase_order_type->Suburb,
                    "Location" => $purchase_order_type->Location,
                    "PostalCode" => $purchase_order_type->PostalCode,
                    "UtilityACAccountCode" => $purchase_order_type->UtilityACAccountCode,
                    "LossACAccountCode" => $purchase_order_type->LossACAccountCode,
                    "DiscountBonusACAccountCode" => $purchase_order_type->DiscountBonusACAccountCode,
                    "CanChangeWarehouse" => $purchase_order_type->CanChangeWarehouse,
                    "TaxClassificationCode" => $purchase_order_type->TaxClassificationCode,
                    "EnableAutomaticCash" => $purchase_order_type->EnableAutomaticCash,
                    "AutomaticCashACAccountCode" => $purchase_order_type->AutomaticCashACAccountCode,
                    "AutomaticCashACPaymentMeanCode" => $purchase_order_type->AutomaticCashACPaymentMeanCode,
                    "ComplementaryDataList" => $purchase_order_type->ComplementaryDataList,
                    "IsDiscountPercent" => $purchase_order_type->IsDiscountPercentaje,
                ],
                "ExternalPrefix" => null,
                "ExternalConsecutive" => null,
                "ExchangePersonalized" => false,
                "CreatedAsProviderByItem" => $purchase_order_type->CreatedAsProviderByItem,
                "IsDiscountPercent" => $purchase_order_type->IsDiscountPercentaje,
            ],
            "lstERPDocItem" => $detalles->toArray(),
            "ERPDocumentTotal" => $erp_document_total,
            "ERPDocumentDue" => null,
            "ERPDocumentPayment" => null,
            "AdvanceValue" => "",
            "PaymentTotal" => "",
            "ERPDocumentConfigModel" => null,
            "JSONERPDocType" => [
                "ERPDocumentTypeID" => $purchase_order_type->ERPDocumentTypeID,
                "Name" => $purchase_order_type->Name,
                "ERPDocClass" => $purchase_order_type->ERPDocClass,
                "ERPDocCode" => $purchase_order_type->ERPDocCode,
                "IsAutomaticEnum" => $purchase_order_type->IsAutomaticEnum,
                "IsDiscountPercentaje" => $purchase_order_type->IsDiscountPercentaje,
                "BaseAIU" => false,
                "TemplateName" => $purchase_order_type->TemplateName,
                "InternalDescription" => $purchase_order_type->InternalDescription,
                "AllowRetVAT" => $purchase_order_type->AllowRetVAT,
                "AllowRetICA" => $purchase_order_type->AllowRetICA,
                "AllowDecimals" => $purchase_order_type->AllowDecimals,
                "AllowSalesBySalesman" => $purchase_order_type->AllowSalesBySalesman,
                "UseCostCenter" => $purchase_order_type->UseCostCenter,
                "IsShowResolutionDIAN" => $purchase_order_type->IsShowResolutionDIAN,
                "ACAccountCode" => $purchase_order_type->ACAccountCode,
                "EnableTwoTaxesOnSave" => false,
                "AllowTaxAdd2" => $purchase_order_type->AllowTaxAdd2,
                "IsConsumptionAddValue" => true,
                "TaxIncluded" => $purchase_order_type->TaxIncluded,
                "TaxImpoByValue" => $purchase_order_type->TaxImpoByValue,
                "CreatedAsProviderByItem" => $purchase_order_type->CreatedAsProviderByItem,
                "ExternalERPDocName" => null,
            ],
        ];
    }

    private function validar_detalles(string $token, string $cookie, array $orden_compra_detalles, array $warehouses, string $date, array $imp_cargo, array $imp_retencion, array $user)
    {
        $validate = [];
        $detalles = [];

        $imp_cargo = collect($imp_cargo)->keyBy('Id')->all();
        $imp_retencion = collect($imp_retencion)->keyBy('Id')->all();

        foreach ($orden_compra_detalles as $row => $detalle) {
            $warehouse = $warehouses[$detalle['bodega']] ?? [];

            if (!isset($detalle['tipo_detalle']) || $detalle['tipo_detalle'] === '') {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El tipo es obligatorio',
                ];
            } elseif (!in_array($detalle['tipo_detalle'], ['0', '1', '2'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El tipo no es valido',
                ];
            }

            if (empty($detalle['item'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El item es obligatorio',
                ];
            }

            $search_product = $this->search_products($token, $cookie, $detalle['item']);
            $product = [];

            if (empty($search_product)) {
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

            if (empty($detalle['bodega'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La bodega es obligatorio',
                ];
            } elseif (empty($warehouse)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La bodega no es valida',
                ];
            }

            if (empty($detalle['cantidad'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La cantidad es obligatorio',
                ];
            } elseif (!is_numeric($detalle['cantidad'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La cantidad debe ser un valor numerico',
                ];
            } elseif ($detalle['cantidad'] == 0) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La cantidad debe ser mayor a 0',
                ];
            }

            if (empty($detalle['valor_unitario'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El valor unitario es obligatorio',
                ];
            } elseif (!is_numeric($detalle['valor_unitario'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El valor unitario debe ser un valor numerico',
                ];
            }

            $detalle['descuento'] = $detalle['descuento'] ?: 0;

            if (!is_numeric($detalle['descuento'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El descuento debe ser un valor numerico',
                ];
            } elseif (is_numeric($detalle['valor_unitario']) && is_numeric($detalle['descuento'])) {
                if ($detalle['descuento'] > $detalle['valor_unitario']) {
                    $validate[] = [
                        'Row' => $row + 1,
                        'ProductCode' => $detalle['item'],
                        'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                        'Error' => 'El descuento no puede ser mayor al precio unitario',
                    ];
                }
            }

            if (empty($detalle['imp_cargo'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto cargo es obligatorio',
                ];
            } elseif (!isset($imp_cargo[$detalle['imp_cargo']])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto cargo no es valido',
                ];
            }

            if (empty($detalle['imp_retencion'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto retencion es obligatorio',
                ];
            } elseif (!isset($imp_retencion[$detalle['imp_retencion']])) {
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

            if (!empty($validate)) continue;

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
                "SalesmanID" => $user['id'],
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
                "pWarehouseList" => json_encode($pWarehouseList)
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

    private function search_products(string $token, string $cookie, string|null $text)
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

        if (!empty($data)) $data['ID'] = $account_id;
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

    private function extraer_token(string $cookie): ?string
    {
        preg_match('/TKNSGRDDREVENTCALZADOSAS=([^;]+)/', $cookie, $matches);

        return $matches[1] ?? null;
    }

    private function sanitize_referencia(string $referencia): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '-', trim($referencia)));
    }
}

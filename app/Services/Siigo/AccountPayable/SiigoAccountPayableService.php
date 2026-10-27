<?php

namespace App\Services\Siigo\AccountPayable;

use App\Mail\AccountPayableAdvanceProviderSiigoMail;
use App\Mail\AccountPayableConciliationProviderSiigoMail;
use App\Mail\AccountPayablePaymentProviderSiigoMail;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoLinks;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Lógica de cuentas por pagar contra Siigo: consultas de proveedores, documentos
 * y pagos, y registro de recibos de pago, anticipos y cruces contables.
 */
class SiigoAccountPayableService
{
    /** Tipo de documento "recibo de pago" usado para pagos y anticipos. */
    private const PAYMENT_RECEIPT_TYPE = '23202';

    private const SIGNATURE = [
        'nombre' => 'Ninoska Fontalvo',
        'cargo' => 'Auxiliar administrativo',
        'departamento' => 'Departamento de Cartera',
        'empresa' => 'Revent Calzado SAS',
        'celular' => '3222792893',
    ];

    public function __construct(private SiigoClient $siigo)
    {
    }

    // ------------------------------------------------------------------
    // Consultas expuestas al controlador
    // ------------------------------------------------------------------

    /** Datos para la pantalla principal. */
    public function screenData(): array
    {
        $token = $this->siigo->token();

        return [
            'type_receipt' => $this->typeReceipt($token, self::PAYMENT_RECEIPT_TYPE),
            'bank_accounts' => $this->bankAccounts($token),
            'type_documents' => $this->typeDocuments($token),
        ];
    }

    /** Pagos del proveedor agrupados por mes. */
    public function paymentsByMonth(int $accountId): array
    {
        $token = $this->siigo->token();

        Carbon::setLocale('es');

        return collect($this->paymentsDocuments($token, $accountId))
            ->map(function ($payment) {
                $payment['Link'] = SiigoLinks::payment($payment['ACEntryID']);

                return $payment;
            })
            ->groupBy(fn ($payment) => Carbon::parse($payment['DocDate'])->format('Y-m'))
            ->map(function ($payments, $periodo) {
                $fecha = Carbon::createFromFormat('Y-m', $periodo);

                return [
                    'mes' => ucfirst($fecha->translatedFormat('F Y')),
                    'total' => $payments->sum('TotalValue'),
                    'detalles' => $payments->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /** Documentos por pagar del proveedor, enlazados con su compra y orden de compra. */
    public function providerDocuments(string $thirdPartyId, int $accountId, bool $allData): array
    {
        $token = $this->siigo->token();

        $provider = $this->provider($token, $thirdPartyId);
        Cache::forever($thirdPartyId, $provider);

        $warehousesById = $allData ? $this->warehousesById($token) : collect();

        $documents = $this->accountsPayableDocuments($token, $accountId);
        $purchases = $this->purchasesDocuments($token, $accountId);

        $purchasesByExternalDocument = collect($purchases)->sortBy('DocDate')->keyBy('ExternalDocumentNumber');

        return collect($documents)
            ->map(function ($document) use ($purchasesByExternalDocument, $token, $accountId, $allData, $warehousesById) {
                $purchase = $purchasesByExternalDocument->get($document['DueName']);

                $document['CreatedDate'] = $purchase['DocDate'] ?? false;
                $document['IsAnnulled'] = $purchase['IsAnnulled'] ?? false;
                $document['DocName'] = $purchase['DocName'] ?? null;
                $document['TotalValue'] = $purchase['TotalValue'] ?? null;
                $document['ACEntryID'] = $purchase['ACEntryID'] ?? null;

                if ($allData) {
                    if (!empty($document['ACEntryID'])) {
                        $purchaseEntry = $this->purchaseEntry($token, $accountId, (int) $document['ACEntryID']);
                        $detail = $this->purchaseEntryDetail($token, (int) $document['ACEntryID']);

                        $document['PurchaseEntry'] = $purchaseEntry;
                        $document['PurchaseEntryDetail'] = [
                            'Observations' => $detail['Observations'],
                            'WarehouseCodes' => $this->warehouseLabels($detail['WarehouseCodes'], $warehousesById),
                            'Quantity' => $detail['Quantity'],
                        ];
                    } else {
                        $document['PurchaseEntry'] = [
                            'quotationID' => null,
                            'docName' => null,
                        ];
                        $document['PurchaseEntryDetail'] = [
                            'Observations' => '',
                            'WarehouseCodes' => '',
                            'Quantity' => 0,
                        ];
                    }
                }

                $document['Links'] = [];
                if ($document['ACEntryID'] ?? null) {
                    $document['Links']['PurchaseInvoice'] = SiigoLinks::purchaseInvoice($document['ACEntryID']);
                }

                if ($document['PurchaseEntry']['quotationID'] ?? null) {
                    $document['Links']['PurchaseOrder'] = SiigoLinks::purchaseOrder($document['PurchaseEntry']['quotationID']);
                }

                return $document;
            })
            ->values()
            ->all();
    }

    /** Proveedores con saldo por pagar. */
    public function providers(bool $sync): array
    {
        $token = $this->siigo->token();

        $body = [
            'Id' => 5438,
            'Skip' => 0,
            'Take' => 0,
            'Sort' => ' ',
            'FilterCriterias' => json_encode([
                $this->filter('_AccountID', 6, [], '', '1'),
                $this->filter('Currency', 65, ['ALL'], 'Moneda Local', null),
            ]),
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

        $response = $this->siigo->report($token, $body);
        $this->ensureSuccessful($response, 'Error consultando cuentas por pagar a proveedores: ');

        $providers = $response->json('data.Value.Table');

        foreach ($providers as &$provider) {
            $data = Cache::get($provider['MsThirdPartyID']);

            if (!$data || $sync) {
                $data = $this->provider($token, $provider['MsThirdPartyID']);
                Cache::forever($provider['MsThirdPartyID'], $data);
            }

            $provider['FullName'] = strtoupper($provider['FullName']);
            $provider['CompanyName'] = strtoupper(data_get($data, 'BasicData.CompanyName', ''));
            $comments = trim(data_get($data, 'Comments', ''));
            $provider['Type'] = str_starts_with($comments, 'PT') ? 'PRODUCTO TERMINADO' : 'OTROS';
        }

        unset($provider);

        return $providers;
    }

    /** Pagos agrupados por semana y proveedor dentro del rango. */
    public function weeklyPayments(Carbon $from, Carbon $to): array
    {
        $token = $this->siigo->token();

        $payments = $this->paymentsDocuments($token, null, $from, $to);

        return collect($payments)
            ->filter(fn ($payment) => !empty($payment['DocDate']))
            ->groupBy(fn ($payment) => Carbon::parse($payment['DocDate'])->startOfWeek(Carbon::MONDAY)->format('Y-m-d'))
            ->sortKeysDesc()
            ->map(function ($weekPayments, $monday) use ($from, $to) {
                $weekStart = Carbon::createFromFormat('Y-m-d', $monday)->startOfDay();

                // La primera y la última semana se recortan al rango consultado
                $start = $weekStart->copy()->max($from->copy()->startOfDay());
                $end = $weekStart->copy()->addDays(6)->min($to->copy()->startOfDay());

                $label = $start->isSameDay($end)
                    ? ucfirst($start->translatedFormat('D j M'))
                    : ucfirst($start->translatedFormat('D j M')) . ' – ' . ucfirst($end->translatedFormat('D j M'));

                $providers = $weekPayments
                    ->groupBy(fn ($payment) => $payment['AccountID'] ?? $payment['Identification'])
                    ->map(function ($providerPayments) {
                        $first = $providerPayments->first();

                        $items = $providerPayments
                            ->sortBy('DocDate')
                            ->values()
                            ->map(function ($payment) {
                                // Total pagado = valor pagado + anticipo
                                $payment['Total'] = (float) ($payment['TotalValue'] ?? 0) + (float) ($payment['TotalAdvance'] ?? 0);
                                $payment['Link'] = SiigoLinks::payment($payment['ACEntryID']);

                                return $payment;
                            });

                        return [
                            'proveedor' => $first['FullName'] ?? '',
                            'identificacion' => $first['Identification'] ?? '',
                            'cantidad' => $items->count(),
                            'total' => $items->sum('Total'),
                            'pagos' => $items->toArray(),
                        ];
                    })
                    ->sortByDesc('total')
                    ->values();

                return [
                    'semana' => $weekStart->isoWeek(),
                    'desde' => $start->format('Y-m-d'),
                    'hasta' => $end->format('Y-m-d'),
                    'label' => $label,
                    'cantidad' => $providers->sum('cantidad'),
                    'total' => $providers->sum('total'),
                    'proveedores' => $providers->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /** HTML de un pago tal como lo muestra Siigo. */
    public function paymentHtml(int $acEntryId): string
    {
        $response = $this->siigo->services($this->siigo->token())
            ->get('/document/api/v2/document-view/GetHtmlView', [
                'ACEntryID' => $acEntryId,
                'IsBase64' => 'false',
            ]);

        $this->ensureSuccessful($response, 'Error consultando detalle de pago ' . $acEntryId . ': ');

        return $response->json()['strHTML'] ?? '';
    }

    // ------------------------------------------------------------------
    // Registro de comprobantes
    // ------------------------------------------------------------------

    /**
     * Recibo de pago contra documentos del proveedor.
     *
     * @param array $data fecha, tipo, accion, origen, valor, observaciones
     * @return array{voucher_id:mixed, voucher:mixed}
     */
    public function registerPayment(array $proveedor, array $documentos, array $data, ?UploadedFile $receipt): array
    {
        $token = $this->siigo->token();

        $warehousesById = $this->warehousesById($token);
        $accountId = $proveedor['AccountID'];

        $items = $this->accountsProvider($token, $accountId, $documentos);

        if (empty($items)) {
            throw new AccountPayableException('No se encontraron documentos para el proveedor y los documentos especificados.', 404);
        }

        if (round((float) $data['valor'], 2) > round((float) collect($items)->sum('Value'), 2)) {
            throw new AccountPayableException('El valor total de los documentos seleccionados no coincide con el valor a pagar.', 400);
        }

        $staged = $this->stageReceipt($receipt);

        try {
            $payload = $this->prepareVoucher($token, $proveedor, $data, $items, $receipt);

            $documentos = $this->enrichDocuments($token, $accountId, $documentos, $warehousesById);

            [$provider, $voucherId, $voucher] = $this->saveAndLoadVoucher($token, $proveedor, $payload);

            $this->notify(new AccountPayablePaymentProviderSiigoMail(
                $provider, $voucher, $documentos, self::SIGNATURE, $data['observaciones'], $voucherId, null,
                $staged['path'] ?? null, $staged['name'] ?? null
            ));
        } finally {
            $this->discardStagedReceipt($staged);
        }

        return ['voucher_id' => $voucherId, 'voucher' => $voucher];
    }

    /**
     * Recibo de anticipo (comprobante sin documentos asociados).
     *
     * @return array{voucher_id:mixed, voucher:mixed}
     */
    public function registerAdvance(array $proveedor, array $data, ?UploadedFile $receipt): array
    {
        $token = $this->siigo->token();

        $staged = $this->stageReceipt($receipt);

        try {
            $payload = $this->prepareVoucher($token, $proveedor, $data, [], $receipt);

            [$provider, $voucherId, $voucher] = $this->saveAndLoadVoucher($token, $proveedor, $payload);

            $this->notify(new AccountPayableAdvanceProviderSiigoMail(
                $provider, $voucher, self::SIGNATURE, $data['observaciones'], $voucherId, null,
                $staged['path'] ?? null, $staged['name'] ?? null
            ));
        } finally {
            $this->discardStagedReceipt($staged);
        }

        return ['voucher_id' => $voucherId, 'voucher' => $voucher];
    }

    /**
     * Cruce contable entre documentos por pagar y recibos (anticipos).
     *
     * @param array $data fecha, tipo, observaciones
     * @return array{voucher_id:mixed, voucher:mixed}
     */
    public function registerConciliation(array $proveedor, array $documentos, array $recibos, array $data): array
    {
        $token = $this->siigo->token();

        $warehousesById = $this->warehousesById($token);
        $accountId = $proveedor['AccountID'];

        $typeReceipt = $this->typeReceipt($token, $data['tipo']);

        $accountDocumentos = $this->searchAccount($token, '22050501');
        $listaDocumentos = $this->searchList($token, $accountDocumentos['ACAccountID'], $accountId, collect($documentos)->pluck('doc.DueName')->toArray());

        $accountRecibos = $this->searchAccount($token, '13300501');
        $listaRecibos = $this->searchList($token, $accountRecibos['ACAccountID'], $accountId, collect($recibos)->pluck('doc.DueName')->toArray());

        $entryType = [
            'ApplyAccountingBook' => $typeReceipt['ApplyAccountingBook'],
            'Class' => $typeReceipt['DocClass'],
            'Code' => $typeReceipt['Code'],
            'Consecutive' => $typeReceipt['Consecutive'],
            'CostCenterDefault' => $typeReceipt['CostCenterDefault'],
            'CostCenterDefaultCode' => $typeReceipt['CostCenterDefault'],
            'CostCenterMandatory' => $typeReceipt['CostCenterMandatory'],
            'DocNumber' => $typeReceipt['Consecutive'],
            'ERPDocumentTypeID' => $typeReceipt['ERPDocumentTypeId'],
            'Name' => $typeReceipt['Title'],
            'Prefix' => $typeReceipt['Prefix'],
            'PrintMsg' => $typeReceipt['PrintMsg'],
            'TemplateName' => $typeReceipt['TemplateName'],
            'UseCostCenter' => $typeReceipt['UseCostCenter'],
            'UseDocumentSupport' => $typeReceipt['UseDocumentSupport'],
        ];

        $entry = [
            'ACEntryID' => -1,
            'AttachmentsFSItemsGUID' => '',
            'CashBookData' => null,
            'DocDate' => Carbon::parse($data['fecha'])->format('Ymd'),
            'DocName' => '',
            'ExchangePersonalized' => false,
            'ExchangeValue' => 0,
            'ExemptBase' => 0,
            'ExtendsFields' => '',
            'ForeignMoneyCode' => '',
            'Observations' => $data['observaciones'],
            'OriginSource' => 0,
            'PayrollPeriodCode' => -1,
            'PayrollVoucherType' => -1,
            'PolicyType' => null,
            'SourceType' => 0,
            'TaxableBase' => 0,
            'TotalValue' => (float) collect($documentos)->sum('covered'),
        ];

        $order = 1;
        $items = [
            ...$this->conciliationItems($listaDocumentos, $accountDocumentos['Name'], 1, $documentos, 'covered', $order),
            ...$this->conciliationItems($listaRecibos, $accountRecibos['Name'], -1, $recibos, 'used', $order),
        ];

        $this->validateEntry($token, $entry['DocDate'], (int) $data['tipo']);

        $payload = [
            'AttachFiles' => [],
            'Entry' => $entry,
            'EntryType' => $entryType,
            'Items' => $items,
            'ModelType' => 21,
        ];

        $documentos = $this->enrichDocuments($token, $accountId, $documentos, $warehousesById);

        $provider = $this->provider($token, $proveedor['MsThirdPartyID']);

        $voucher = $this->saveConciliation($token, $payload);
        $voucherId = $voucher['code'];

        $this->notify(new AccountPayableConciliationProviderSiigoMail(
            $provider, $voucher, $documentos, $recibos, self::SIGNATURE, $data['observaciones'], $voucherId
        ));

        return ['voucher_id' => $voucherId, 'voucher' => $voucher];
    }

    // ------------------------------------------------------------------
    // Pasos compartidos del registro
    // ------------------------------------------------------------------

    /**
     * Arma el comprobante de pago/anticipo: tipo de recibo, encabezado, adjunto en Siigo y validación.
     *
     */
    private function prepareVoucher(string $token, array $proveedor, array $data, array $items, ?UploadedFile $receipt): array
    {
        $typeReceipt = $this->typeReceipt($token, self::PAYMENT_RECEIPT_TYPE);

        $entryType = [
            'ERPDocumentTypeID' => $typeReceipt['ERPDocumentTypeId'],
            'Name' => $typeReceipt['Title'],
            'Class' => $typeReceipt['DocClass'],
            'Code' => $typeReceipt['Code'],
            'ACAccountCode' => -1,
            'CostCenterDefault' => $typeReceipt['CostCenterDefault'],
            'CostCenterMandatory' => $typeReceipt['CostCenterMandatory'],
            'InternalDescription' => $typeReceipt['InternalDescription'],
            'IsAutomaticEnum' => $typeReceipt['IsAutomaticEnum'],
            'TemplateName' => $typeReceipt['TemplateName'],
            'UseCostCenter' => $typeReceipt['UseCostCenter'],
        ];

        $entry = [
            'ACEntryID' => -1,
            'DocNumber' => -1,
            'DocName' => '',
            'Observations' => $data['observaciones'],
            'DocDate' => Carbon::parse($data['fecha'])->format('Ymd'),
            'ACEntryCode' => -1,
            'ACPaymentMeanCode' => (int) $data['origen'],
            'AccountCode' => $proveedor['AccountID'],
            'AttachmentsFSItemsGUID' => '',
            'ExchangePersonalized' => false,
            'ExchangeValue' => 0,
            'ExtendsFields' => '',
            'ForeignMoneyCode' => '',
            'IsClosePeriod' => false,
            'PaymentMethod' => null,
            'PaymentMethodCFDI' => null,
            'SourceType' => 0,
            'TotalValue' => (float) $data['valor'],
            'VoucherType' => $data['accion'],
        ];

        $attachFiles = $this->attachReceipt($token, $receipt);

        $this->validateEntry($token, $entry['DocDate'], (int) $data['tipo']);

        return [
            'AttachFiles' => $attachFiles,
            'Entry' => $entry,
            'EntryType' => $entryType,
            'Items' => $items,
            'ModelType' => 4,
            'TreasuryPayment' => null,
        ];
    }

    /** Sube el comprobante a Siigo y devuelve la lista AttachFiles del comprobante. */
    private function attachReceipt(string $token, ?UploadedFile $file): array
    {
        if (!$file) {
            return [];
        }

        $uploaded = $this->uploadAttachment($token, (string) Str::uuid(), $file);
        $attachFiles = [[
            'Name' => $uploaded['Name'] ?? $file->getClientOriginalName(),
            'GUID' => $uploaded['GUID'] ?? null,
            'Extension' => $uploaded['Extension'] ?? ('.' . $file->getClientOriginalExtension()),
            'Type' => $uploaded['Type'] ?? '-image',
            'shortName' => Str::limit($uploaded['Name'] ?? '', 20, '...'),
        ]];

        $this->updateAttachFilesReferences($token, $attachFiles);

        return $attachFiles;
    }

    /**
     * Copia el comprobante a una ruta temporal privada (storage/app/tmp) para
     * adjuntarlo al correo. Nunca queda en un disco público.
     *
     * @return array{path:string, name:string}|null
     */
    private function stageReceipt(?UploadedFile $file): ?array
    {
        if (!$file) {
            return null;
        }

        $dir = storage_path('app/tmp');
        File::ensureDirectoryExists($dir);

        $path = $dir . DIRECTORY_SEPARATOR . Str::uuid() . '.' . $file->getClientOriginalExtension();
        File::copy($file->getRealPath(), $path);

        return ['path' => $path, 'name' => $file->getClientOriginalName()];
    }

    /** Elimina el temporal del comprobante (se llama siempre, también si algo falla). */
    private function discardStagedReceipt(?array $staged): void
    {
        if ($staged && File::exists($staged['path'])) {
            File::delete($staged['path']);
        }
    }

    /** @return array{0:mixed, 1:mixed, 2:mixed} [proveedor Siigo, id del comprobante, comprobante] */
    private function saveAndLoadVoucher(string $token, array $proveedor, array $payload): array
    {
        $provider = $this->provider($token, $proveedor['MsThirdPartyID']);
        $voucherId = $this->saveVoucher($token, $payload);
        $voucher = $this->searchVoucher($token, $voucherId);

        return [$provider, $voucherId, $voucher];
    }

    /** Agrega a cada documento seleccionado su compra, orden y bodegas. */
    private function enrichDocuments(string $token, int $accountId, array $documentos, Collection $warehousesById): array
    {
        foreach ($documentos as &$item) {
            $purchaseEntry = $this->purchaseEntry($token, $accountId, (int) $item['doc']['ACEntryID']);
            $detail = $this->purchaseEntryDetail($token, (int) $item['doc']['ACEntryID']);

            $item['doc']['PurchaseEntry'] = $purchaseEntry;
            $item['doc']['PurchaseEntryDetail'] = [
                'DocDate' => $detail['DocDate'],
                'Observations' => $detail['Observations'],
                'WarehouseCodes' => $this->warehouseLabels($detail['WarehouseCodes'], $warehousesById),
                'Quantity' => $detail['Quantity'],
            ];
        }
        unset($item);

        return $documentos;
    }

    /** Renglones del cruce contable: uno por cada cuota encontrada en Siigo. */
    private function conciliationItems(array $list, string $accountName, int $dc, array $selected, string $valueKey, int &$order): array
    {
        $items = [];

        foreach ($list as $row) {
            $items[] = [
                'ACAccountCode' => $row['ACAccountCode'],
                'ACPaymentMean' => null,
                'AccountCode' => $row['AccountCode'],
                'BaseValue' => 0,
                'ConsumptionValue' => 0,
                'CostCenterCode' => $row['CostCenterCode'],
                'DC' => $dc,
                'Description' => "{$row['DuePrefix']}-{$row['DueConsecutive']} Cuota: {$row['DueQuote']} Fecha: {$row['DueDate']}",
                'DueConsecutive' => $row['DueConsecutive'],
                'DueDate' => $row['DueDateFormat'],
                'DuePrefix' => $row['DuePrefix'],
                'DueQuote' => $row['DueQuote'],
                'EntryItemType' => 0,
                'FixedAssetCode' => null,
                'LongDescription' => $accountName,
                'Order' => $order,
                'ProductCode' => null,
                'Quantity' => 0,
                'TaxAddCode' => null,
                'TaxAddName' => '',
                'TaxAddPercentage' => 0,
                'TaxAddValue' => 0,
                'TaxDiscCode' => null,
                'TaxDiscName' => '',
                'TaxDiscPercentage' => 0,
                'TaxDiscValue' => 0,
                'Value' => collect($selected)->firstWhere('doc.DueName', $row['DuePrefix'] . '-' . $row['DueConsecutive'])[$valueKey] ?? 0,
            ];
            $order++;
        }

        return $items;
    }

    private function notify(object $mailable): void
    {
        // Los contactos del proveedor no reciben copia; solo contabilidad.
        $emails = [];

        Mail::to([config('siigo.mail.accounting'), ...$emails])->send($mailable);
    }

    private function warehouseLabels(array $codes, Collection $warehousesById): string
    {
        return collect($codes)
            ->map(function ($code) use ($warehousesById) {
                $warehouse = $warehousesById->get($code);

                return $code . '-' . ($warehouse['name'] ?? '');
            })
            ->implode(', ');
    }

    // ------------------------------------------------------------------
    // Llamadas a Siigo
    // ------------------------------------------------------------------

    private function ensureSuccessful(Response $response, string $message): void
    {
        if (!$response->successful()) {
            throw new \Exception($message . $response->body());
        }
    }

    private function provider(string $token, string $uuid)
    {
        $response = $this->siigo->services($token)
            ->get(config('siigo.endpoints.services.catalog') . "/third-party/account/{$uuid}");

        $this->ensureSuccessful($response, 'Error consultando balance proveedor: ');

        return $response->json('AccountDto');
    }

    private function accountsProvider(string $token, int $accountId, array $documentos)
    {
        $response = $this->siigo->services($token)
            ->get('/ACEntryApi/api/v1/Voucher/GetDuesClient', [
                'id' => $accountId,
                'moneyid' => '',
                'voucherType' => 0,
                'modeltype' => 4,
            ]);

        $this->ensureSuccessful($response, 'Error consultando balance proveedor: ');

        $data = json_decode($response->json('dataJSONResult'), true);

        if (!is_array($data)) {
            throw new \Exception('Siigo no devolvió documentos válidos para este proveedor.');
        }

        return collect($data)
            ->filter(function ($item) use ($documentos) {
                $documento = ($item['DuePrefix'] ?? '') . '-' . ($item['DueConsecutive'] ?? '');

                return in_array($documento, collect($documentos)->pluck('doc.DueName')->toArray());
            })
            ->map(function ($item) use ($documentos) {
                $item['Value'] = collect($documentos)->firstWhere('doc.DueName', $item['DuePrefix'] . '-' . $item['DueConsecutive'])['covered'] ?? 0;

                return $item;
            })
            ->values()
            ->toArray();
    }

    private function uploadAttachment(string $token, string $parentPathGuid, UploadedFile $file, int $acEntryCode = -1, int $referenceType = 216)
    {
        $path = '/isiigo2ACDocumentAPI/api/v1/Document/UploadFile?' . http_build_query([
            'parentPathGUID' => $parentPathGuid,
        ]);

        $response = $this->siigo->services($token)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post($path, [
                'ACEntryCode' => $acEntryCode,
                'ReferenceType' => $referenceType,
            ]);

        $this->ensureSuccessful($response, 'Error subiendo el archivo adjunto: ');

        $data = $response->json();

        if (!($data['success'] ?? false)) {
            throw new \Exception('Siigo rechazó la subida del comprobante: ' . $response->body());
        }

        return $data;
    }

    private function updateAttachFilesReferences(string $token, array $attachFiles, $entryCode = -1, int $referenceType = 216)
    {
        $response = $this->siigo->services($token)
            ->post('/ACEntryApi/api/v2/Invoice/UpdateReferecesAttachFiles', [
                'AttachFiles' => $attachFiles,
                'EntryCode' => (string) $entryCode,
                'ReferenceType' => (string) $referenceType,
            ]);

        $this->ensureSuccessful($response, 'Error actualizando referencias de archivos adjuntos: ');

        return $response->json();
    }

    private function validateEntry(string $token, string $date, int $erpDocumentTypeId, int $consecutive = -1, int $acEntryId = -1)
    {
        $response = $this->siigo->services($token)
            ->get('/ACEntryApi/api/v1/ACEntryValidator/IsValid', [
                'Date' => $date,
                'ERPDocumentTypeID' => $erpDocumentTypeId,
                'Consecutive' => $consecutive,
                'ACEntryID' => $acEntryId,
            ]);

        $this->ensureSuccessful($response, 'Error validando el comprobante: ');

        $data = $response->json();

        if (!($data['success'] ?? false)) {
            throw new \Exception('El comprobante no pudo ser validado por Siigo.');
        }

        if ($data['inUse'] ?? false) {
            throw new \Exception('El consecutivo del comprobante ya se encuentra en uso, intenta de nuevo.');
        }

        return $data;
    }

    private function saveVoucher(string $token, array $payload)
    {
        $response = $this->siigo->services($token)->post('/ACEntryApi/api/v1/Voucher/Save/', $payload);

        $this->ensureSuccessful($response, 'Error guardando el comprobante de pago: ');

        return $response->json();
    }

    private function saveConciliation(string $token, array $payload)
    {
        $response = $this->siigo->services($token)->post('/ACEntryApi/api/v1/JournalEntry/Save/', $payload);

        $this->ensureSuccessful($response, 'Error guardando el comprobante de contable: ');

        return $response->json();
    }

    private function searchVoucher(string $token, int $id)
    {
        $response = $this->siigo->services($token)
            ->get('/ACEntryApi/api/v1/Invoice/GetDataView', ['id' => $id]);

        $this->ensureSuccessful($response, 'Error consultando el comprobante de pago: ');

        return $response->json();
    }

    private function purchaseEntry(string $token, int $accountId, int $acEntryId)
    {
        $response = $this->siigo->services($token)
            ->get('/document/api/v2/cards-view/CardsInfo', [
                'ACEntryID' => $acEntryId,
                'AccountID' => $accountId,
            ]);

        $this->ensureSuccessful($response, 'Error consultando detalle de compra ' . $acEntryId . ': ');

        $data = $response->json()['accounts'];

        return [
            'quotationID' => $data['quotationID'] ?? null,
            'docName' => $data['docName'] ?? '',
        ];
    }

    private function purchaseEntryDetail(string $token, int $acEntryId)
    {
        $response = $this->siigo->services($token)
            ->get(config('siigo.endpoints.services.purchase_item'), ['id' => $acEntryId]);

        $this->ensureSuccessful($response, 'Error consultando detalle de compra ' . $acEntryId . ': ');

        $data = $response->json();

        Cache::forever("FC-ID-{$data['Entry']['ACEntryID']}", $data);

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
            'Quantity' => $items->sum(fn ($item) => (float) ($item['Quantity'] ?? 0)),
        ];
    }

    private function warehouses(string $token): array
    {
        $response = $this->siigo->api($token)->get(config('siigo.endpoints.api.warehouses'));

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    private function warehousesById(string $token): Collection
    {
        return collect($this->warehouses($token))->keyBy('id');
    }

    private function typeReceipt(string $token, string|int $erpDocumentTypeId)
    {
        $response = $this->siigo->services($token)
            ->get('/ACGeneralApi/api/v1/JournalEntryType/GetById', [
                'ERPDocumentTypeId' => $erpDocumentTypeId,
            ]);

        $this->ensureSuccessful($response, 'Error consultando tipos de recibo de pago: ');

        return $response->json();
    }

    private function bankAccounts(string $token)
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

            $response = $this->siigo->services($token)
                ->retry(3, 3000)
                ->asJson()
                ->post(config('siigo.endpoints.services.autocomplete'), $body);

            $this->ensureSuccessful($response, 'Error consultando cuentas: ');

            $data = $response->json();

            if (is_string($data)) {
                if ($data === '') {
                    break;
                }
                $data = json_decode($data, true);
            }

            if (empty($data)) {
                break;
            }
            $resultados = array_merge($resultados, $data);
            $numRecordView += 10;
        } while (true);

        return $resultados;
    }

    private function typeDocuments(string $token)
    {
        $response = $this->siigo->services($token)
            ->get('/ACGeneralApi/api/v1/JournalEntryType/GetActiveJournalEntryAutomaticEnumTypes');

        $this->ensureSuccessful($response, 'Error consultando tipos de documentos: ');

        $data = json_decode($response->json(), true);

        return collect($data)->whereIn('ERPDocCode', ['21'])->values();
    }

    private function searchAccount(string $token, string $query)
    {
        $data = $this->autocomplete($token, [
            'browserID' => '3',
            'filter' => '(IsTransactional = 1 AND IsActive = 1)',
            'query' => $query,
            'tags' => (object) [],
            'type' => 1,
        ]);

        if ($data === null) {
            return null;
        }

        return collect($data)->firstWhere('Code', $query);
    }

    private function searchList(string $token, string $ACAccountCode, string $AccountCode, array $docs)
    {
        $data = $this->autocomplete($token, [
            'browserID' => '9',
            'filter' => "(Balance <> 0 or ForeignBalance <> 0) AND ACAccountCode = $ACAccountCode AND AccountCode = $AccountCode AND ISNULL(MoneyCode, '') = ''",
            'query' => '',
            'tags' => (object) [],
            'type' => 1,
        ]);

        if ($data === null) {
            return null;
        }

        return collect($data)
            ->filter(function ($item) use ($docs) {
                $documento = $item['DuePrefix'] . '-' . $item['DueConsecutive'];

                return in_array($documento, $docs);
            })
            ->values()
            ->toArray();
    }

    /** Autocomplete de Siigo: devuelve el JSON doblemente codificado ya decodificado, o null si no hay datos. */
    private function autocomplete(string $token, array $body): ?array
    {
        $response = $this->siigo->services($token)
            ->post(config('siigo.endpoints.services.autocomplete'), $body);

        $this->ensureSuccessful($response, 'Error consultando la cuenta contable: ');

        $rawBody = trim($response->body());

        if ($rawBody === '""' || $rawBody === '' || $rawBody === 'null') {
            return null;
        }

        $data = json_decode($response->json(), true);

        if (empty($data)) {
            return null;
        }

        return json_decode($data, true);
    }

    // ------------------------------------------------------------------
    // Reportes paginados (getreport)
    // ------------------------------------------------------------------

    private function accountsPayableDocuments(string $token, int $accountId): array
    {
        return $this->reportRows($token, 'Error consultando documentos del proveedor: ', fn (int $skip, int $take, bool $count) => [
            'Id' => 5436,
            'Skip' => $skip,
            'Take' => $take,
            'Sort' => ' ',
            'FilterCriterias' => json_encode([
                $this->filter('_vClientProv', 68, [$accountId], '', 'Account'),
                $this->filter('_vDueAgeEnum', 7, [-1], '', 'DueAgeEnum'),
                $this->filter('Currency', 65, ['ALL'], 'Moneda Local', null),
            ]),
            'Params' => json_encode([
                'TabID' => '1609',
                'DueType' => '-1',
                'pTabID' => '538',
                'rReport' => '1',
            ]),
            'GetTotalCount' => $count,
            'GridOrderCriteria' => null,
            'AddOns' => null,
        ]);
    }

    private function purchasesDocuments(string $token, int $accountId): array
    {
        $fechaInicio = Carbon::now()->subYear(2);
        $fechaFin = Carbon::now();

        return $this->transactionsReport(
            $token,
            'Error consultando documentos de compra: ',
            '0',
            'Compra',
            [$accountId, false, 'AccountID'],
            2016,
            $fechaInicio,
            $fechaFin
        );
    }

    private function paymentsDocuments(string $token, ?int $accountId = null, ?Carbon $fechaInicio = null, ?Carbon $fechaFin = null): array
    {
        $fechaInicio = $fechaInicio ?? Carbon::now()->startOfYear();
        $fechaFin = $fechaFin ?? Carbon::now();

        return $this->transactionsReport(
            $token,
            'Error consultando pagos: ',
            '1',
            'Pagos',
            $accountId ? [$accountId, false, 'AccountID'] : [],
            2015,
            $fechaInicio,
            $fechaFin
        );
    }

    /** Reporte de transacciones de compras (5451), por tipo (compra o pago). */
    private function transactionsReport(
        string $token,
        string $error,
        string $transactionType,
        string $transactionLabel,
        array $providerValue,
        int $firstYear,
        Carbon $fechaInicio,
        Carbon $fechaFin
    ): array {
        $source = collect(range($firstYear, $fechaFin->year))
            ->map(fn ($anio) => [
                'id' => $anio,
                'StartDate' => "{$anio}0101",
                'EndDate' => "{$anio}1231",
            ])
            ->values()
            ->toArray();

        $filterCriterias = json_encode([
            $this->filter('_vTypeTransaction', 7, [$transactionType], $transactionLabel, 'PurchasesTransactionEnum'),
            $this->filter('_vProvider', 68, $providerValue, '', 'Account'),
            $this->filter(
                '_vDocDate',
                76,
                [$fechaInicio->format('Ymd'), $fechaFin->format('Ymd')],
                $fechaInicio->format('Y/m/d') . ' - ' . $fechaFin->format('Y/m/d'),
                $source
            ),
            $this->filter('_vUser', 6, [], '', '12'),
            $this->filter('_vProviderInvoice', 6, [], '', '64'),
            $this->filter('_vESiigoStatus', 7, ['-1'], '', 'DianStateFilterEnum'),
        ]);

        return $this->reportRows($token, $error, fn (int $skip, int $take, bool $count) => [
            'Id' => 5451,
            'Skip' => $skip,
            'Take' => $take,
            'Sort' => ' ',
            'FilterCriterias' => $filterCriterias,
            'Params' => json_encode(['TabID' => '1408']),
            'GetTotalCount' => $count,
            'GridOrderCriteria' => null,
            'AddOns' => [$this->posWebAddOn()],
        ]);
    }

    /**
     * Recorre un reporte de Siigo de 100 en 100 filas.
     *
     * @param callable $body fn (int $skip, int $take, bool $getTotalCount): array
     */
    private function reportRows(string $token, string $error, callable $body): array
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        do {
            $response = $this->siigo->report($token, $body($skip, $take, $total === null));

            $this->ensureSuccessful($response, $error);

            if ($total === null) {
                $total = (int) $response->json('totalCount');
            }

            $rows = array_merge($rows, $response->json('data.Value.Table') ?? []);

            $skip += $take;
        } while ($skip < $total);

        return $rows;
    }

    private function filter(string $field, int $type, array $value, string $valueUi, mixed $source): array
    {
        return [
            'Field' => $field,
            'FilterType' => $type,
            'OperatorType' => 0,
            'Value' => $value,
            'ValueUI' => $valueUi,
            'Source' => $source,
        ];
    }

    private function posWebAddOn(): array
    {
        return [
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
        ];
    }
}

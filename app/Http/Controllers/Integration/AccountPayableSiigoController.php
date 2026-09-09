<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\SiigoInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AccountPayableSiigoController extends Controller
{
    public function account_payable()
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();
        $type_payment_receipts = $this->type_payment_receipts($token);
        $types = [
            0 => "Abono a deuda",
            1 => "Anticipo",
            2 => "Avanzado (Impuestos, descuentos y ajustes)",
        ];
        $bank_accounts = $this->bank_accounts($token);
        $providers = $this->accounts_payable_providers($token);

        return view('integration.account_payable', compact('providers', 'type_payment_receipts', 'types', 'bank_accounts'));
    }

    public function account_payable_documents(Request $request, int $accountId)
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

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
            'docName' => $data['docName'] ?? null
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
            'Observations' => $data['Entry']['Observations'] ?? null,
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

    private function type_payment_receipts(string $token)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->get('https://services.siigo.com/ACGeneralApi/api/v1/JournalEntryType/GetById', [
                'ERPDocumentTypeId' => '23202',
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
}

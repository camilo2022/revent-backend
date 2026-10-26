<?php

namespace App\Jobs;

use App\Exports\InvoiceSiigoMultiSheetExport;
use App\Jobs\Concerns\LoadsSiigoCatalogs;
use App\Jobs\Concerns\SendsExportMail;
use App\Mail\ExportInvoice360SiigoMail;
use App\Services\SiigoInventoryService;
use App\Services\Siigo\SiigoLinks;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportInvoice360SiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsExportMail, LoadsSiigoCatalogs;

    public int $timeout = 3600;
    public int $tries = 1;

    private array $documents = [];

    public function __construct(public array $filters, public array|string $notifyEmail)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $sellers = $this->loadSellers($token, $this->filters);
        $costCenters = $this->loadCostCenters($token);
        $this->loadReport5430($token, $costCenters, $this->filters['created_start'], $this->filters['created_end']);
        $invoices = $this->loadListing(config('siigo.endpoints.api.invoices'), $token, $this->filters);
        $creditNotes = $this->loadListing(config('siigo.endpoints.api.credit_notes'), $token, $this->filters);

        $purchases = $siigo->getPurchases($token, $this->filters);
        $products = $siigo->getProducts($token, $this->filters);

        $filename = $this->exportFilename('facturas_de_venta_360');

        $this->storeAndNotify(new InvoiceSiigoMultiSheetExport(
                $sellers,
                $costCenters,
                $invoices,
                $creditNotes,
                $purchases,
                $products,
                $this->stores(),
                $this->documents
            ), $filename);
    }

    protected function mailClass(): string
    {
        return ExportInvoice360SiigoMail::class;
    }

    private function loadReport5430(string $token, array $costCenters, string $createdStart, string $createdEnd): void
    {
        $dateFrom = Carbon::parse($createdStart)->format('Ymd');
        $dateTo = Carbon::parse($createdEnd)->format('Ymd');
        foreach($costCenters as $costCenterId => $cost_center) {
            $title = base64_encode($cost_center['name']);

            $filter = base64_encode(json_encode([
                ['Field' => 'AccountID', 'Value' => []],
                ['Field' => 'DocDate', 'Value' => [$dateFrom, $dateTo, null]],
                ['Field' => '_var_IncudeCreditNote', 'Value' => ['1']],
                ['Field' => 'CostCenterCode', 'Value' => []],
                ['Field' => 'Currency', 'Value' => ['ALL']]
            ], JSON_UNESCAPED_UNICODE));

            $condition = base64_encode(
                base64_encode(" AND ED.CostCenterCode = {$costCenterId}")
            );

            $params = json_encode([
                'IncludeRetICAAndRetIVA' => '1',
                'Title' => $title,
                'Filter' => $filter,
                'LngTitle' => '28848',
                'Condition' => $condition,
                'TabID' => '1636',
            ], JSON_UNESCAPED_UNICODE);

            $body = [
                'AddOns' => [
                    [
                        'name' => 'POS Web',
                        'state' => true,
                        'tenantId' => '0x00000000000000000000000000605286',
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
                'FilterCriterias' => '[]',
                'GetTotalCount' => false,
                'GridOrderCriteria' => null,
                'Id' => 5430,
                'Params' => $params,
                'Skip' => 0,
                'Sort' => ' ',
                'Take' => 0,
            ];

            $response = $this->siigoClient()->services($token)
                ->timeout(180)
                ->asJson()
                ->post(config('siigo.endpoints.services.acreport'), $body);

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            $data = $response->json('data.Value.Table');

            foreach($data as $item) {
                $this->documents[$item['DocName']] = [
                    'url' => SiigoLinks::salesInvoice($item['ACEntryID']),
                    'date' => $item['DocDate'],
                    'name' => strtoupper($item['FullName'])
                ];
            }
        }
    }
}

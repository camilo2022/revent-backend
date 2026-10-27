<?php

namespace App\Jobs;

use App\Exports\InvoiceSiigoMultiSheetExport;
use App\Jobs\Concerns\LoadsSiigoCatalogs;
use App\Jobs\Concerns\SendsExportMail;
use App\Mail\ExportInvoiceSiigoMail;
use App\Services\Siigo\SiigoSalesDocumentsService;
use App\Services\SiigoInventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reporte de facturación de venta: un solo Excel con la hoja 360 (resumen por factura con la
 * lógica de las vendedoras) y la hoja de detalle de la facturación.
 */
class ExportInvoiceSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsExportMail, LoadsSiigoCatalogs;

    public int $timeout = 7200;
    public int $tries = 1;

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
        $invoices = $this->loadListing(config('siigo.endpoints.api.invoices'), $token, $this->filters);
        $creditNotes = $this->loadListing(config('siigo.endpoints.api.credit_notes'), $token, $this->filters);

        $purchases = $siigo->getPurchases($token, $this->filters);
        $products = $siigo->getProducts($token, $this->filters);

        $documents = app(SiigoSalesDocumentsService::class)->byCostCenter(
            $token,
            $costCenters,
            $this->filters['created_start'],
            $this->filters['created_end']
        );

        $this->storeAndNotify(new InvoiceSiigoMultiSheetExport(
                $sellers,
                $costCenters,
                $invoices,
                $creditNotes,
                $purchases,
                $products,
                $this->stores(),
                $documents
            ), $this->exportFilename('facturas_de_venta'));
    }

    protected function mailClass(): string
    {
        return ExportInvoiceSiigoMail::class;
    }
}

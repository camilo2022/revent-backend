<?php

namespace App\Jobs;

use App\Exports\InvoiceDetailSiigoExport;
use App\Jobs\Concerns\LoadsSiigoCatalogs;
use App\Jobs\Concerns\SendsExportMail;
use App\Mail\ExportInvoiceSiigoMail;
use App\Services\SiigoInventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $sellers = $this->loadSellers($token, $this->filters);
        $costCenters = $this->loadCostCenters($token);
        $invoices = $this->loadListing(config('siigo.endpoints.api.invoices'), $token, $this->filters);
        $creditNotes = $this->loadListing(config('siigo.endpoints.api.credit_notes'), $token, $this->filters);

        $purchases = $siigo->getPurchases($token, $this->filters);
        $products = $siigo->getProducts($token, $this->filters);

        $filename = $this->exportFilename('facturas_de_venta');

        $this->storeAndNotify(new InvoiceDetailSiigoExport(
                $sellers,
                $costCenters,
                $invoices,
                $creditNotes,
                $purchases,
                $products,
                $this->stores(),
                false
            ), $filename);
    }

    protected function mailClass(): string
    {
        return ExportInvoiceSiigoMail::class;
    }
}

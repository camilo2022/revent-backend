<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Integration\Concerns\ExportsToEmail;
use App\Http\Requests\Integration\Export\InvoiceExportRequest;
use App\Jobs\ExportInvoiceSiigoJob;

class InvoiceSiigoController extends Controller
{
    use ExportsToEmail;

    public function export_invoice()
    {
        return view('integration.export_invoice');
    }

    public function export_invoice_download(InvoiceExportRequest $request)
    {
        return $this->dispatchDateRangeExport($request, ExportInvoiceSiigoJob::class, 'integration.export_invoice_download');
    }
}

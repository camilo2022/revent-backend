<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Integration\Concerns\ExportsToEmail;
use App\Http\Requests\Integration\Export\Invoice360ExportRequest;
use App\Jobs\ExportInvoice360SiigoJob;

class Invoice360SiigoController extends Controller
{
    use ExportsToEmail;

    public function export_invoice_360()
    {
        return view('integration.exports.invoice_360.form');
    }

    public function export_invoice_360_download(Invoice360ExportRequest $request)
    {
        return $this->dispatchDateRangeExport($request, ExportInvoice360SiigoJob::class, 'integration.exports.invoice_360.result');
    }
}

<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Integration\Concerns\ExportsToEmail;
use App\Http\Requests\Integration\Export\PurchaseExportRequest;
use App\Jobs\ExportPurchaseSiigoJob;

class PurchaseSiigoController extends Controller
{
    use ExportsToEmail;

    public function export_purchase()
    {
        return view('integration.exports.purchase.form');
    }

    public function export_purchase_download(PurchaseExportRequest $request)
    {
        return $this->dispatchDateRangeExport($request, ExportPurchaseSiigoJob::class, 'integration.exports.purchase.result');
    }
}

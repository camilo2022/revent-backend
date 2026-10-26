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
        return view('integration.export_purchase');
    }

    public function export_purchase_download(PurchaseExportRequest $request)
    {
        return $this->dispatchDateRangeExport($request, ExportPurchaseSiigoJob::class, 'integration.export_purchase_download');
    }
}

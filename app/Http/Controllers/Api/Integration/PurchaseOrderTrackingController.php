<?php

namespace App\Http\Controllers\Api\Integration;

use App\Exports\InvoicePurchaseOrderMissingExport;
use App\Http\Controllers\Api\Integration\Concerns\RespondsWithFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\PurchaseOrderTracking\DocumentsRequest;
use App\Http\Requests\Api\Integration\PurchaseOrderTracking\MissingDownloadRequest;
use App\Http\Resources\Api\Integration\PurchaseOrderTracking\PurchaseOrderTrackingResource;
use App\Services\Integration\PurchaseOrderTrackingService;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;

/** Seguimiento de órdenes de compra y Excel de referencias faltantes. */
class PurchaseOrderTrackingController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure, RespondsWithFile;

    public function __construct(private PurchaseOrderTrackingService $tracking)
    {
    }

    public function documents(DocumentsRequest $request)
    {
        try {
            return $this->successResponse(
                new PurchaseOrderTrackingResource($this->tracking->documents(Auth::user())),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function missingDownload(MissingDownloadRequest $request)
    {
        try {
            $rows = $this->tracking->missingRows(Auth::user());

            return $this->downloadExcel(new InvoicePurchaseOrderMissingExport($rows), 'referencias_faltantes_' . now()->format('Ymd_His'));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

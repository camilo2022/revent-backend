<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Api\Integration\Concerns\BuildsDateRangeFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\Exports\Invoice360ExportRequest;
use App\Http\Requests\Api\Integration\Exports\InventoryExportRequest;
use App\Http\Requests\Api\Integration\Exports\InvoiceExportRequest;
use App\Http\Requests\Api\Integration\Exports\PurchaseExportRequest;
use App\Http\Resources\Api\Integration\Exports\ExportQueuedResource;
use App\Jobs\ExportInventorySiigoJob;
use App\Jobs\ExportInvoice360SiigoJob;
use App\Jobs\ExportInvoiceSiigoJob;
use App\Jobs\ExportPurchaseSiigoJob;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * Exportes de Siigo por correo (inventario, facturas, facturas 360 y compras). Cada uno despacha el mismo job
 * y con los mismos parámetros que las pantallas de Integration; la respuesta es 202 porque el archivo llega por correo.
 */
class ExportController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure, BuildsDateRangeFilters;

    public function inventory(InventoryExportRequest $request)
    {
        try {
            $validated = $request->validated();

            $emails = $request->input('emails') ?: config('siigo.mail.inventory');

            $baseFilters = [
                'page_size' => $validated['page_size'] ?? 100,
                'type' => $validated['type'] ?? 'Product',
            ];

            $positiveValues = !empty($validated['inventory_type'])
                ? [$validated['inventory_type'] === 'positivo']
                : [true, false];

            foreach ($positiveValues as $positive) {
                ExportInventorySiigoJob::dispatch([...$baseFilters, 'positive' => $positive], $emails);
            }

            return $this->queued($emails, count($positiveValues));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function invoice(InvoiceExportRequest $request)
    {
        return $this->dispatchDateRangeExport($request, ExportInvoiceSiigoJob::class);
    }

    public function invoice360(Invoice360ExportRequest $request)
    {
        return $this->dispatchDateRangeExport($request, ExportInvoice360SiigoJob::class);
    }

    public function purchase(PurchaseExportRequest $request)
    {
        return $this->dispatchDateRangeExport($request, ExportPurchaseSiigoJob::class);
    }

    /** Despacha el job con el rango de fechas validado. */
    private function dispatchDateRangeExport($request, string $job)
    {
        try {
            $emails = $request->input('emails') ?: config('siigo.mail.exports');

            $job::dispatch($this->dateRangeFilters($request->validated()), $emails);

            return $this->queued($emails, 1);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    private function queued(array|string $emails, int $jobs)
    {
        return $this->successResponse(
            new ExportQueuedResource(['emails' => $emails, 'jobs' => $jobs]),
            'El exporte se está generando. Llegará por correo cuando termine.',
            202
        );
    }
}

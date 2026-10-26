<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Integration\Concerns\ExportsToEmail;
use App\Http\Requests\Integration\Export\InventoryExportRequest;
use App\Jobs\ExportInventorySiigoJob;

class InventorySiigoController extends Controller
{
    use ExportsToEmail;

    public function export_inventory()
    {
        return view('integration.export_inventory');
    }

    public function export_inventory_download(InventoryExportRequest $request)
    {
        $validated = $request->validated();

        $emails = $request->input('emails', config('siigo.mail.inventory'));

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

        return view('integration.export_inventory_download', compact('emails'));
    }
}

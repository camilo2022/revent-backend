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
        return view('integration.exports.inventory.form');
    }

    public function export_inventory_download(InventoryExportRequest $request)
    {
        $validated = $request->validated();

        $emails = $request->input('emails', config('siigo.mail.inventory'));

        $baseFilters = [
            'page_size' => $validated['page_size'] ?? 100,
            'type' => $validated['type'] ?? 'Product',
        ];

        // Un solo Excel con una hoja por tipo; sin selección salen las dos (positivo y negativo).
        $types = match ($validated['inventory_type'] ?? null) {
            'positivo' => ['positive'],
            'negativo' => ['negative'],
            default => ['positive', 'negative'],
        };

        ExportInventorySiigoJob::dispatch([...$baseFilters, 'types' => $types], $emails);

        return view('integration.exports.inventory.result', compact('emails'));
    }
}

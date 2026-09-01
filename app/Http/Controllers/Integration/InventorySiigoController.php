<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ExportInventorySiigoJob;

class InventorySiigoController extends Controller
{
    public function export_inventory()
    {
        return view('integration.export_inventory');
    }

    public function export_inventory_download(Request $request)
    {
        $validated = $request->validate([
            'emails' => 'nullable|array|min:1',
            'emails.*' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@revent\.com\.co$/'],
            'inventory_type'  => 'nullable|in:positivo,negativo',
            'page_size' => 'nullable|integer|min:1|max:1000',
            'type' => 'nullable|string|in:Product,Variant',
        ], [
            'emails.*.regex' => 'El correo :input debe pertenecer al dominio @revent.com.co',
        ]);

        $defaultEmails = [
            'operaciones@revent.com.co',
            'ingenieria@revent.com.co',
            'leanmanagement@revent.com.co',
            'tecnologia@revent.com.co',
            'auditoria@revent.com.co',
            'inventario@revent.com.co'
        ];

        $emails = $request->input('emails', $defaultEmails);

        $baseFilters = [
            'page_size' => $validated['page_size'] ?? 100,
            'type' => $validated['type'] ?? 'Product',
        ];

        if (!empty($validated['inventory_type'])) {
            $positiveValues = [$validated['inventory_type'] === 'positivo'];
        } else {
            $positiveValues = [true, false];
        }

        foreach ($positiveValues as $positive) {
            ExportInventorySiigoJob::dispatch(
                [...$baseFilters, 'positive' => $positive],
                $emails
            );
        }

        return view('integration.export_inventory_download', compact('emails'));
    }
}

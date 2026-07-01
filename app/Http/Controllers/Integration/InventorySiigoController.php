<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ExportInventorySiigoJob;

class InventorySiigoController extends Controller
{
    public function export_inventory(Request $request)
    {
        $emails = [
            'operaciones@revent.com.co',
            'ingenieria@revent.com.co',
            'leanmanagement@revent.com.co',
            'camiloacacio16@gmail.com',
        ];

        $baseFilters = [
            'created_start' => $request->input('created_start'),
            'created_end' => $request->input('created_end'),
            'page_size' => $request->input('page_size', 100),
            'type' => $request->input('type', 'Product'),
        ];

        foreach ([true, false] as $positive) {
            ExportInventorySiigoJob::dispatch(
                [...$baseFilters, 'positive' => $positive],
                $request->input('email', $emails)
            );
        }

        return response()->json([
            'success' => true,
            'message' => '⏳ Exportación en proceso. Te notificaremos por email cuando esté lista.',
        ]);
    }
}

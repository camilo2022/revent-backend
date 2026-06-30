<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ExportInventorySiigoJob;

class InventorySiigoController extends Controller
{
    public function export_inventory(Request $request)
    {
        $filters = [
            'created_start' => $request->input('created_start'),
            'created_end'   => $request->input('created_end'),
            'page_size'     => $request->input('page_size', 100),
            'type'          => $request->input('type', 'Product'),
            'positive'      => $request->boolean('positive', true),
        ];

        ExportInventorySiigoJob::dispatch(
            $filters,
            $request->boolean('positive', true),
            $request->input('email', 'camiloacacio16@gmail.com') // o sacarlo del usuario autenticado
        );

        return response()->json([
            'success' => true,
            'message' => '⏳ Exportación en proceso. Te notificaremos por email cuando esté lista.',
        ]);
    }
}

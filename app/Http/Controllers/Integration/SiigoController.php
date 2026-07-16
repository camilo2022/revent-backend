<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Jobs\SyncUnicoSiigoJob;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SiigoController extends Controller
{
    use ApiMessage, ApiResponser;

    public function sync(Request $request)
    {
        SyncUnicoSiigoJob::dispatch([
            'created_start' => $request->input('created_start', Carbon::now()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s')),
            'created_end' => $request->input('created_end', Carbon::now()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s')),
            'page' => $request->input('page', 1),
            'page_size' => $request->input('page_size', 100),
        ]);

        return response()->json([
            'success' => true,
            'message' => '⏳ Sincronización de ventas con unico en proceso. Te notificaremos por email cuando esté lista.',
        ]);
    }
}

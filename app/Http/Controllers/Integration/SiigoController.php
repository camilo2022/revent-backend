<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\Siigo\SyncUnicoRequest;
use App\Jobs\SyncUnicoSiigoJob;
use App\Services\Siigo\SyncUnicoRunLog;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Carbon\Carbon;

class SiigoController extends Controller
{
    use ApiMessage, ApiResponser;

    public function sync(SyncUnicoRequest $request, SyncUnicoRunLog $log)
    {
        $start = $request->input('created_start', Carbon::now()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s'));
        $end = $request->input('created_end', Carbon::now()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s'));

        SyncUnicoSiigoJob::dispatch([
            'created_start' => $start,
            'created_end' => $end,
            'page' => $request->input('page', 1),
            'page_size' => $request->input('page_size', 100),
            'source' => SyncUnicoRunLog::API,
            'run_id' => $log->start(SyncUnicoRunLog::API, $start, $end),
        ]);

        return response()->json([
            'success' => true,
            'message' => '⏳ Sincronización de ventas con unico en proceso. Te notificaremos por email cuando esté lista.',
        ]);
    }
}

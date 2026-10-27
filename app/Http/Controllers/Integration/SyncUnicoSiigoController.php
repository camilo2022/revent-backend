<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\Siigo\ManualSyncUnicoRequest;
use App\Jobs\SyncUnicoSiigoJob;
use App\Services\Siigo\SyncUnicoRunLog;
use Illuminate\Support\Facades\Cache;

/** Pantalla para lanzar a mano la sincronización de ventas de Siigo con Único y ver cómo han salido las últimas. */
class SyncUnicoSiigoController extends Controller
{
    /** Segundos que se bloquea un nuevo envío manual, para evitar un doble clic. */
    private const LOCK_SECONDS = 60;

    public function __construct(private SyncUnicoRunLog $log)
    {
    }

    public function index()
    {
        return view('integration.sync_unico.index', [
            'runs' => $this->log->recent(),
            'notifyEmail' => config('siigo.mail.technology'),
        ]);
    }

    public function run(ManualSyncUnicoRequest $request)
    {
        if (!Cache::add('siigo.sync_unico.manual_lock', true, self::LOCK_SECONDS)) {
            return back()
                ->withErrors(['mode' => 'Ya se lanzó una sincronización hace menos de un minuto. Revisa el historial antes de lanzar otra.'])
                ->withInput();
        }

        [$start, $end, $label] = $request->period();

        SyncUnicoSiigoJob::dispatch([
            'created_start' => $start,
            'created_end' => $end,
            'page' => 1,
            'page_size' => 100,
            'source' => SyncUnicoRunLog::MANUAL,
            'run_id' => $this->log->start(SyncUnicoRunLog::MANUAL, $start, $end),
        ]);

        return redirect()
            ->route('siigo.sync_unico_manual')
            ->with('status', "Sincronización lanzada para {$label}. El resultado llegará por correo y quedará en el historial.");
    }
}

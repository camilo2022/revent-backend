<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\SyncUnico\RunRequest;
use App\Http\Requests\Api\Integration\SyncUnico\RunsRequest;
use App\Http\Resources\Api\Integration\SyncUnico\SyncUnicoRunCollection;
use App\Http\Resources\Api\Integration\SyncUnico\SyncUnicoRunResource;
use App\Jobs\SyncUnicoSiigoJob;
use App\Services\Siigo\SyncUnicoRunLog;
use App\Support\Integration\LegacyCacheKeys;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Cache;

/** Sincronización manual de ventas de Siigo con Único: historial de las últimas ejecuciones y lanzamiento. */
class SyncUnicoController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    public function __construct(private SyncUnicoRunLog $log)
    {
    }

    public function runs(RunsRequest $request)
    {
        try {
            return $this->successResponse(new SyncUnicoRunCollection([
                'runs' => $this->log->recent(),
                'notify_email' => config('siigo.mail.technology'),
            ]), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function run(RunRequest $request)
    {
        try {
            // Mismo bloqueo que la pantalla de Integration: evita un doble clic desde cualquiera de los dos lados
            if (!Cache::add(LegacyCacheKeys::syncUnicoManualLock(), true, LegacyCacheKeys::SYNC_UNICO_LOCK_SECONDS)) {
                return $this->errorResponse([
                    'message' => 'Ya se lanzó una sincronización hace menos de un minuto. Revisa el historial antes de lanzar otra.',
                    'error' => 'Too Many Requests',
                ], 429);
            }

            [$start, $end, $label] = $request->period();

            $runId = $this->log->start(SyncUnicoRunLog::MANUAL, $start, $end);

            SyncUnicoSiigoJob::dispatch([
                'created_start' => $start,
                'created_end' => $end,
                'page' => 1,
                'page_size' => 100,
                'source' => SyncUnicoRunLog::MANUAL,
                'run_id' => $runId,
            ]);

            return $this->successResponse(
                new SyncUnicoRunResource(['run_id' => $runId, 'label' => $label, 'created_start' => $start, 'created_end' => $end]),
                "Sincronización lanzada para {$label}. El resultado llegará por correo y quedará en el historial.",
                202
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

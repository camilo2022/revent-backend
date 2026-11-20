<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\EffiSync\CompareRequest;
use App\Http\Requests\Api\Integration\EffiSync\PlanRequest;
use App\Http\Requests\Api\Integration\EffiSync\StatusRequest;
use App\Http\Requests\Api\Integration\EffiSync\SyncRequest;
use App\Http\Resources\Api\Integration\EffiSync\EffiSyncResource;
use App\Jobs\EffiInventorySyncJob;
use App\Services\Effi\EffiAutoSync;
use App\Services\Effi\EffiClient;
use App\Services\Effi\EffiInventorySyncPlanner;
use App\Services\Effi\EffiWebInventoryComparator;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Cache;

/**
 * Sincronización de la bodega WEB de Siigo con Effi desde el front. Usa los mismos servicios y el mismo job que la
 * pantalla web (siigo/effi_sync), así que comparten el bloqueo y el avance: nunca corren dos sincronizaciones a la vez.
 * compare y plan solo consultan; sync escribe en Effi y solo funciona con EFFI_SYNC_ENABLED=true.
 */
class EffiSyncController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    /** Inventario de Siigo y de Effi código por código, con sus diferencias. */
    public function compare(CompareRequest $request, EffiWebInventoryComparator $comparator)
    {
        set_time_limit(300);

        try {
            return $this->successResponse(new EffiSyncResource($comparator->compare(), 'comparison'), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Qué se cambiaría en Effi y las decisiones que faltan. No escribe nada. */
    public function plan(PlanRequest $request, EffiInventorySyncPlanner $planner, EffiClient $effi, EffiAutoSync $auto)
    {
        set_time_limit(300);

        try {
            $plan = $planner->plan($effi);

            if (!($plan['ok'] ?? false)) {
                throw new \DomainException($plan['message'] ?? 'No se pudo armar el plan.');
            }

            $plan['sincronizacion_activa'] = (bool) config('effi.sync_enabled');
            $plan['correos'] = $auto->recipients();

            return $this->successResponse(new EffiSyncResource($plan, 'plan'), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Lanza la sincronización en segundo plano; el resultado llega al correo de la bodega WEB. */
    public function sync(SyncRequest $request, EffiAutoSync $auto)
    {
        try {
            if (!config('effi.sync_enabled')) {
                throw new \DomainException('La sincronización con Effi está desactivada en el servidor (EFFI_SYNC_ENABLED).');
            }

            if ($this->running()) {
                return $this->errorResponse(['message' => 'Ya hay una sincronización con Effi en curso. Espera a que termine.', 'error' => 'Conflict'], 409);
            }

            // Mismo arranque que la pantalla web: el avance queda en «planeando» hasta que el worker tome el job
            Cache::put(EffiInventorySyncJob::STATUS_KEY, ['estado' => 'planeando', 'fase' => 'En segundo plano (cola «' . config('effi.queue') . '»)'], 86400);

            EffiInventorySyncJob::dispatch($request->decisions(), $request->testCode());

            return $this->successResponse(
                new EffiSyncResource(['correos' => $auto->recipients(), 'solo' => $request->testCode()], 'sync'),
                'La sincronización con Effi se está ejecutando. El resultado llegará al correo de la bodega WEB.',
                202
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Avance de la última sincronización (web, API o automática). */
    public function status(StatusRequest $request)
    {
        try {
            return $this->successResponse(new EffiSyncResource(Cache::get(EffiInventorySyncJob::STATUS_KEY), 'status'), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    private function running(): bool
    {
        return in_array(Cache::get(EffiInventorySyncJob::STATUS_KEY)['estado'] ?? null, ['planeando', 'ejecutando'], true);
    }
}

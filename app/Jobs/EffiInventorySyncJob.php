<?php

namespace App\Jobs;

use App\Mail\EffiSyncFailedMail;
use App\Mail\EffiSyncResultMail;
use App\Services\Effi\EffiAutoSync;
use App\Services\Effi\EffiClient;
use App\Services\Effi\EffiInventorySyncPlanner;
use App\Services\Effi\EffiInventorySyncRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sincroniza la bodega de Effi con la bodega WEB de Siigo, en segundo plano. Va en su propia cola (config effi.queue,
 * por defecto «sync-siigo-effi»): necesita un worker: php artisan queue:work --queue=sync-siigo-effi --timeout=3600 --tries=1
 * Al terminar (bien, con errores o fallida) manda un correo al correo de la bodega WEB.
 */
class EffiInventorySyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;
    public int $timeout = 3600;

    public const STATUS_KEY = 'effi_sync_status';
    public const LOCK_KEY = 'effi_sync_lock';

    /** @param array $decisions lo que eligió la persona en la vista: categorias y duplicados (ver EffiInventorySyncPlanner::applyDecisions) */
    /** @param string|null $only si viene, se sincroniza SOLO ese código (prueba) */
    public function __construct(private array $decisions = [], private ?string $only = null)
    {
        $this->onQueue(config('effi.queue'));
    }

    private function notifyFailure(string $reason): void
    {
        try {
            Mail::to(app(EffiAutoSync::class)->recipients())->send(new EffiSyncFailedMail($reason));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de fallo de la sincronización Effi', ['error' => $e->getMessage()]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Sincronización Effi falló', ['error' => $e->getMessage()]);
        Cache::put(self::STATUS_KEY, ['estado' => 'error', 'mensaje' => $e->getMessage(), 'actualizado' => now()->format('d/m/Y h:i:s A')], 86400);
        $this->notifyFailure($e->getMessage());
    }

    /** Lo que queda para revisar a mano después de sincronizar, con el motivo: avisos del plan, pasos que fallaron y códigos que no se tocaron. */
    public static function review(array $plan, array $errors): array
    {
        $review = [];

        foreach ($plan['items'] as $item) {
            $reasons = array_merge($item['avisos'], isset($errors[$item['codigo']]) ? ['No se pudo completar: ' . $errors[$item['codigo']]] : []);

            if ($reasons) {
                $review[] = ['codigo' => $item['codigo'], 'descripcion' => $item['descripcion'], 'motivos' => $reasons];
            }
        }

        foreach ($plan['manuales'] as $manual) {
            $review[] = ['codigo' => $manual['codigo'], 'descripcion' => $manual['descripcion'], 'motivos' => ['No se tocó: ' . $manual['motivo']]];
        }

        return $review;
    }

    public function handle(EffiInventorySyncPlanner $planner, EffiInventorySyncRunner $runner, EffiClient $effi, EffiAutoSync $auto): void
    {
        set_time_limit(0);

        $lock = Cache::lock(self::LOCK_KEY, 3600);

        $status = fn (array $data) => Cache::put(self::STATUS_KEY, $data + ['actualizado' => now()->format('d/m/Y h:i:s A')], 86400);

        if (!$lock->get()) {
            $status(['estado' => 'error', 'mensaje' => 'Hay otra sincronización en curso (por ejemplo la automática tras un traslado). Intenta de nuevo cuando termine.']);
            $this->notifyFailure('Hay otra sincronización en curso (por ejemplo la automática tras un traslado). No se cambió nada: intenta de nuevo cuando termine.');

            return;
        }

        try {
            $status(['estado' => 'planeando', 'fase' => 'Consultando Siigo y Effi']);
            $plan = $planner->plan($effi);

            if ($plan['ok']) {
                $plan = $planner->applyDecisions($plan, $this->decisions);
            }

            if ($plan['ok'] && $this->only !== null) {
                $plan['items'] = array_values(array_filter($plan['items'], fn ($i) => $i['codigo'] === $this->only));
                $plan['manuales'] = [];

                if (!$plan['items']) {
                    $message = "El código {$this->only} no tiene cambios pendientes (ya está igual o necesita una decisión).";
                    $status(['estado' => 'error', 'mensaje' => $message]);
                    $this->notifyFailure($message);

                    return;
                }
            }

            if (!$plan['ok']) {
                $status(['estado' => 'error', 'mensaje' => $plan['message']]);
                $this->notifyFailure($plan['message']);

                return;
            }

            $result = $runner->run($plan, fn (string $phase, array $done) => $status(['estado' => 'ejecutando', 'fase' => $phase, 'hechos' => $done]));

            Log::info('Sincronización Effi terminada', $result['hechos'] + ['errores' => count($result['errores'])]);
            $status(['estado' => 'terminado', 'hechos' => $result['hechos'], 'errores' => $result['errores'], 'revision' => self::review($plan, $result['errores'])]);

            // Quien la lanzó ya no está mirando la pantalla: el resultado llega por correo
            Mail::to($auto->recipients())->send(new EffiSyncResultMail($auto->report($plan, $result, $this->only !== null ? [['documento' => "Prueba con un solo código: {$this->only}", 'sentido' => 'Solo se sincronizó ese código']] : [], manual: true)));
        } catch (\Throwable $e) {
            Log::error('Sincronización Effi falló', ['error' => $e->getMessage()]);
            $status(['estado' => 'error', 'mensaje' => $e->getMessage()]);
            $this->notifyFailure($e->getMessage());
        } finally {
            $lock->release();
        }
    }
}

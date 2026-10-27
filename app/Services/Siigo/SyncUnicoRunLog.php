<?php

namespace App\Services\Siigo;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Historial corto de las sincronizaciones de Siigo con Único (automáticas y manuales), para que la
 * pantalla de sincronización muestre cuáles terminaron bien, cuáles fallaron y cuáles siguen sin ejecutarse.
 *
 * Cada ejecución se registra al despacharla ("en proceso") y se actualiza cuando el job termina.
 */
class SyncUnicoRunLog
{
    public const AUTOMATIC = 'automática';
    public const MANUAL = 'manual';
    public const API = 'API';

    private const CACHE_KEY = 'SIIGO:SYNC-UNICO:RUNS';
    private const KEEP = 15;

    /** Más allá de este tiempo sin terminar se considera que la cola no la ejecutó. */
    private const STALE_MINUTES = 90;

    /** Registra una sincronización recién despachada y devuelve su identificador. */
    public function start(string $source, string $createdStart, string $createdEnd): string
    {
        $runId = (string) Str::uuid();

        $this->save([
            'run_id' => $runId,
            'source' => $source,
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'status' => 'running',
            'range' => [$createdStart, $createdEnd],
            'invoices' => null,
            'sent' => null,
            'inserted' => null,
            'rejected' => null,
            'error' => null,
        ]);

        return $runId;
    }

    /** Marca la ejecución como terminada bien. */
    public function succeeded(?string $runId, array $params, int $invoices, array $unicoSummary): void
    {
        $this->finish($runId, $params, [
            'status' => 'success',
            'invoices' => $invoices,
            'sent' => $unicoSummary['enviadas'] ?? null,
            'inserted' => $unicoSummary['insertadas'] ?? null,
            'rejected' => $unicoSummary['rechazadas'] ?? null,
            'error' => null,
        ]);
    }

    /** Marca la ejecución como fallida. */
    public function failed(?string $runId, array $params, string $error): void
    {
        $this->finish($runId, $params, ['status' => 'failed', 'error' => mb_substr($error, 0, 600)]);
    }

    /**
     * Últimas ejecuciones, la más reciente primero. Las que llevan mucho tiempo "en proceso" salen como "sin_respuesta".
     *
     * @return array<int,array>
     */
    public function recent(): array
    {
        return array_map(function (array $run) {
            if ($run['status'] === 'running' && Carbon::parse($run['started_at'])->lt(now()->subMinutes(self::STALE_MINUTES))) {
                $run['status'] = 'stale';
            }

            return $run;
        }, Cache::get(self::CACHE_KEY, []));
    }

    private function finish(?string $runId, array $params, array $changes): void
    {
        $runs = Cache::get(self::CACHE_KEY, []);

        foreach ($runs as &$run) {
            if ($runId !== null && $run['run_id'] === $runId) {
                $run = array_merge($run, $changes, ['finished_at' => now()->toIso8601String()]);
                Cache::forever(self::CACHE_KEY, $runs);

                return;
            }
        }
        unset($run);

        // Ejecución sin registro previo (por ejemplo, una lanzada antes de existir este historial).
        $this->save(array_merge([
            'run_id' => $runId ?? (string) Str::uuid(),
            'source' => $params['source'] ?? self::API,
            'started_at' => now()->toIso8601String(),
            'finished_at' => now()->toIso8601String(),
            'range' => [$params['created_start'] ?? null, $params['created_end'] ?? null],
            'invoices' => null, 'sent' => null, 'inserted' => null, 'rejected' => null, 'error' => null,
        ], $changes));
    }

    private function save(array $run): void
    {
        $runs = Cache::get(self::CACHE_KEY, []);
        array_unshift($runs, $run);

        Cache::forever(self::CACHE_KEY, array_slice($runs, 0, self::KEEP));
    }
}

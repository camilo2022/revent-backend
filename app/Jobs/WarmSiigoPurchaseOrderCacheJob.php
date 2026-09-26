<?php
// app/Jobs/WarmSiigoPurchaseOrderCacheJob.php

namespace App\Jobs;

use App\Services\SiigoInventoryService;
use App\Services\SiigoPurchaseOrderCacheService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cachea (Cache::forever, igual que hoy) el detalle de las órdenes de
 * compra y facturas de compra de Siigo dentro de un tramo de fechas.
 *
 * Se despacha en tramos pequeños (ver comando
 * siigo:warm-purchase-cache) en vez de un único job para todo el
 * rango, porque un rango de varios meses puede traer miles de
 * documentos y cada uno implica una petición HTTP individual a Siigo
 * (fetch_detail) — hacerlo todo en un solo job se demoraría muchísimo
 * y sería más difícil de reintentar si algo falla a mitad de camino.
 */
class WarmSiigoPurchaseOrderCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Reintentos ante fallos de red/timeout con Siigo.
     */
    public int $tries = 3;

    /**
     * Backoff entre reintentos (segundos).
     */
    public int $backoff = 60;

    /**
     * Tiempo máximo del job. Un tramo de 7 días puede traer varios
     * cientos de documentos, cada uno con su propia petición HTTP.
     */
    public int $timeout = 1800;

    public function __construct(
        public string $from, // 'Y-m-d'
        public string $to,   // 'Y-m-d'
    ) {
    }

    public function handle(SiigoPurchaseOrderCacheService $cacheService): void
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $fecha_inicio = Carbon::parse($this->from)->startOfDay();
        $fecha_fin = Carbon::parse($this->to)->endOfDay();

        Log::info("[WarmSiigoPurchaseOrderCacheJob] Cacheando tramo {$this->from} -> {$this->to}...");

        $result = $cacheService->warmRange($token, $fecha_inicio, $fecha_fin);

        Log::info('[WarmSiigoPurchaseOrderCacheJob] Tramo cacheado', $result);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[WarmSiigoPurchaseOrderCacheJob] Falló el tramo {$this->from} -> {$this->to}: " . $exception->getMessage());
    }
}

<?php
// app/Console/Commands/WarmSiigoPurchaseOrderCache.php

namespace App\Console\Commands;

use App\Jobs\WarmSiigoPurchaseOrderCacheJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Precalienta el cache de órdenes de compra / facturas de compra de
 * Siigo desde hace N meses hasta AYER (hoy se consulta en vivo desde
 * el controlador), despachando un Job por cada tramo de fechas
 * (por defecto, 3 días) con un pequeño delay entre uno y otro.
 *
 * Cada ejecución SOBRESCRIBE el cache existente.
 *
 * Uso:
 *   php artisan siigo:warm-purchase-cache
 *   php artisan siigo:warm-purchase-cache --months=5 --chunk-days=3 --delay=30
 *
 * Requiere un worker de colas corriendo:
 *   php artisan queue:work
 */
class WarmSiigoPurchaseOrderCache extends Command
{
    protected $signature = 'siigo:warm-purchase-cache
        {--months=5 : Cuántos meses atrás cachear, contados desde hoy}
        {--chunk-days=3 : Tamaño de cada tramo, en días}
        {--delay=30 : Segundos de espera entre cada tramo despachado a la cola}';

    protected $description = 'Precalienta en cache (por tramos) el listado y detalle de órdenes de compra y facturas de compra de Siigo, desde hace N meses hasta ayer';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $chunkDays = max(1, (int) $this->option('chunk-days'));
        $delaySeconds = max(0, (int) $this->option('delay'));

        // Hoy no se cachea: el controlador lo consulta en vivo
        //$fecha_inicio = Carbon::now()->subMonths($months)->startOfDay();
        $fecha_inicio = Carbon::parse('2026-04-01')->startOfDay();
        $fecha_fin = Carbon::yesterday()->endOfDay();

        $this->info("Cacheando desde {$fecha_inicio->toDateString()} hasta {$fecha_fin->toDateString()}, en tramos de {$chunkDays} día(s)...");

        $cursor = $fecha_inicio->copy();
        $tramo = 0;
        $delay = 0;

        while ($cursor->lte($fecha_fin)) {
            $desde = $cursor->copy();
            $hasta = $cursor->copy()->addDays($chunkDays - 1);

            if ($hasta->gt($fecha_fin)) {
                $hasta = $fecha_fin->copy();
            }

            WarmSiigoPurchaseOrderCacheJob::dispatch($desde->toDateString(), $hasta->toDateString())->delay(now()->addSeconds($delay));

            $tramo++;

            $this->line("Tramo {$tramo}: {$desde->toDateString()} -> {$hasta->toDateString()} (delay {$delay}s)");

            $cursor->addDays($chunkDays);
            $delay += $delaySeconds;
        }

        $this->info("Se despacharon {$tramo} tramo(s) a la cola. Asegúrate de tener un worker corriendo (php artisan queue:work).");

        return self::SUCCESS;
    }
}

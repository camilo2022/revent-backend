<?php

namespace App\Console\Commands;

use App\Jobs\WarmTransferDatesSiigoJob;
use App\Services\SiigoInventoryService;
use Illuminate\Console\Command;

class WarmTransferDatesSiigo extends Command
{
    protected $signature = 'siigo:warm-transfer-dates {--warehouse= : Solo esta bodega (id)}';

    protected $description = 'Actualiza la caché de fechas de ingreso por traslado que usa el reporte de inventario (una tarea por bodega)';

    /** Segundos entre el inicio de cada bodega, para no golpear a Siigo de golpe. */
    private const STAGGER_SECONDS = 20;

    public function handle(): int
    {
        $stores = (new SiigoInventoryService())->stores();
        $excluded = config('siigo.inventory.excluded_warehouses');

        $ids = $this->option('warehouse')
            ? [(int) $this->option('warehouse')]
            : array_values(array_diff(array_keys($stores), $excluded));

        foreach ($ids as $i => $id) {
            WarmTransferDatesSiigoJob::dispatch((int) $id)->delay(now()->addSeconds($i * self::STAGGER_SECONDS));
        }

        $this->info('Tareas despachadas: ' . count($ids) . '. La primera vez recorren todo el historial y tardan; después solo lo reciente.');

        return self::SUCCESS;
    }
}

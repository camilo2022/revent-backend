<?php

namespace App\Jobs;

use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoTransferEntriesService;
use App\Services\SiigoInventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** Actualiza la caché de fechas de ingreso por traslado de una bodega (ver SiigoTransferEntriesService). */
class WarmTransferDatesSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 2;
    public int $backoff = 120;

    public function __construct(private int $warehouseId)
    {
    }

    public function handle(SiigoTransferEntriesService $transfers, SiigoCatalogService $catalog): void
    {
        ini_set('memory_limit', '-1');

        $token = (new SiigoInventoryService())->auth();

        $count = $transfers->refresh($token, $this->warehouseId, $catalog->warehouses($token));

        Log::info("[WarmTransferDatesSiigoJob] Bodega {$this->warehouseId}: {$count} productos con fecha de ingreso por traslado.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[WarmTransferDatesSiigoJob] Bodega {$this->warehouseId}: " . $e->getMessage());
    }
}

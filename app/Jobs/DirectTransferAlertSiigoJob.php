<?php

namespace App\Jobs;

use App\Mail\DirectTransferAlertSiigoMail;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoDirectTransferAlertService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Revisa los traslados directos que hizo una bodega en un día y, si hubo alguno, avisa por correo.
 * Sin traslados directos no se envía nada.
 */
class DirectTransferAlertSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 2;
    public int $backoff = 120;

    public function __construct(private int $warehouseId, private string $date)
    {
    }

    public function handle(SiigoClient $client, SiigoCatalogService $catalog, SiigoDirectTransferAlertService $alerts): void
    {
        ini_set('memory_limit', '-1');

        $token = $client->token();
        $warehouses = $catalog->warehouses($token);
        $day = Carbon::parse($this->date);

        $transfers = $alerts->detect($token, $this->warehouseId, $day, $warehouses);

        if (empty($transfers)) {
            return;
        }

        $warehouse = collect($warehouses)->first(fn ($w) => (int) $w['id'] === $this->warehouseId);

        Mail::to(config('siigo.mail.direct_transfers'))->send(new DirectTransferAlertSiigoMail(
            warehouse: $warehouse['name'] ?? (string) $this->warehouseId,
            date: $day->format('d/m/Y'),
            transfers: $transfers,
        ));
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[DirectTransferAlertSiigoJob] Bodega {$this->warehouseId} ({$this->date}): " . $e->getMessage());
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Exports\InventorySiigoExport;
use App\Jobs\Concerns\SendsExportMail;
use App\Mail\ExportInventorySiigoMail;
use App\Services\Siigo\SiigoTransferEntriesService;
use App\Services\SiigoInventoryService;

class ExportInventorySiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsExportMail;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(private array $filters, private string|array $notifyEmail)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        $siigo = new SiigoInventoryService();

        $name = $this->filters['positive'] ? "inventarios_con_ingreso" : "inventarios_por_ingreso";
        $filename = $this->exportFilename($name, 'Ymd_His');

        $token = $siigo->auth();

        $purchases = $siigo->getPurchases($token, $this->filters);

        $stores = $siigo->stores();

        $this->storeAndNotify(new InventorySiigoExport(
                $name,
                $token,
                $purchases,
                $stores,
                $this->filters,
                config('siigo.urls.api'),
                $this->transferDates($stores)
            ), $filename);
    }

    /** Fechas de ingreso por traslado por bodega (de la caché que actualiza siigo:warm-transfer-dates). */
    private function transferDates(array $stores): array
    {
        $transfers = app(SiigoTransferEntriesService::class);
        $dates = [];

        foreach (array_keys($stores) as $warehouseId) {
            $dates[$warehouseId] = $transfers->cached((int) $warehouseId);
        }

        return $dates;
    }

    protected function mailClass(): string
    {
        return ExportInventorySiigoMail::class;
    }
}

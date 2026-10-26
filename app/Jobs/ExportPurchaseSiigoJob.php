<?php

namespace App\Jobs;

use App\Exports\PurchaseSiigoExport;
use App\Jobs\Concerns\LoadsSiigoCatalogs;
use App\Jobs\Concerns\SendsExportMail;
use App\Mail\ExportPurchaseSiigoMail;
use App\Services\SiigoInventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportPurchaseSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsExportMail, LoadsSiigoCatalogs;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(public array $filters, public array|string $notifyEmail)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $costCenters = $this->loadCostCenters($token);
        $purchases = $this->loadPurchases($token, $this->filters);

        $products = $siigo->getProducts($token, $this->filters);

        $filename = $this->exportFilename('compras');

        $this->storeAndNotify(new PurchaseSiigoExport($token, $costCenters, $purchases, $products, $this->stores(), config('siigo.urls.api')), $filename);
    }

    protected function mailClass(): string
    {
        return ExportPurchaseSiigoMail::class;
    }

    /** Compras del rango pedido (se descartan las que la API devuelve fuera de él). */
    private function loadPurchases(string $token, array $filters): array
    {
        $createdStart = isset($filters['created_start']) ? new \DateTime($filters['created_start']) : null;
        $createdEnd = isset($filters['created_end']) ? new \DateTime($filters['created_end']) : null;

        $purchases = [];

        foreach ($this->loadListing(config('siigo.endpoints.api.purchases'), $token, $filters) as $item) {
            $itemDate = new \DateTime($item['date']);

            $withinRange = (!$createdStart || $itemDate >= $createdStart)
                && (!$createdEnd || $itemDate <= $createdEnd);

            if ($withinRange) {
                $purchases[] = $item;
            }
        }

        return $purchases;
    }
}

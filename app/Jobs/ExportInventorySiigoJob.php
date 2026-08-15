<?php
// app/Jobs/ExportInventorySiigoJob.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Exports\InventorySiigoExport;
use App\Mail\ExportInventorySiigoMail;
use App\Services\SiigoInventoryService;
use Maatwebsite\Excel\Facades\Excel;

class ExportInventorySiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        private array $filters,
        private string|array $notifyEmail
    ) {}

    public function handle(): void
    {
        $siigo = new SiigoInventoryService();

        $name = $this->filters['positive'] ? "inventarios_con_ingreso" : "inventarios_por_ingreso";
        $filename = "reports/{$name}_" . now()->format('Ymd_His') . ".xlsx";

        $token = $siigo->auth();

        $purchases = $siigo->getPurchases($token, $this->filters);

        Excel::store(
            new InventorySiigoExport(
                $name,
                $token,
                $purchases,
                $siigo->stores(),
                $this->filters,
                config('services.siigo.base_url')
            ),
            "exports/{$filename}",
            'public'
        );

        $downloadUrl = route('exports.download', ['file' => $filename]);

        Mail::to($this->notifyEmail)->send(new ExportInventorySiigoMail(success: true, filename: $filename, downloadUrl: $downloadUrl));
    }

    public function failed(\Throwable $e): void
    {
        Mail::to($this->notifyEmail)->send(new ExportInventorySiigoMail(success: false, errorMessage: $e->getMessage()));
    }
}

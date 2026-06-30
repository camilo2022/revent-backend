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
use App\Services\SiigoInventoryService;
use Maatwebsite\Excel\Facades\Excel;

class ExportInventorySiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0;      // Sin límite de tiempo
    public int $tries = 1;        // No reintentar si falla

    public function __construct(
        private array $filters,
        private bool $positive,
        private string $notifyEmail  // A quién notificar cuando termine
    ) {}

    public function handle(): void
    {
        $siigo = new SiigoInventoryService();

        $name = $this->positive ? "inventarios_con_ingreso" : "inventarios_por_ingreso";
        $filename = "{$name}_" . now()->format('Ymd_His') . ".xlsx";

        // Autenticar
        $token = $siigo->auth();

        // Obtener compras
        $purchases = $siigo->getPurchases($token, $this->filters);

        // Generar Excel y guardarlo en storage/app/exports/
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
            'local'
        );

        // Notificar por email con link de descarga
        $downloadUrl = route('exports.download', ['file' => $filename]);

        Mail::raw(
            "✅ Tu exportación de inventario Siigo está lista.\n\nDescarga: {$downloadUrl}",
            fn ($msg) => $msg
                ->to($this->notifyEmail)
                ->subject("Exportación lista: {$filename}")
        );
    }

    public function failed(\Throwable $e): void
    {
        Mail::raw(
            "❌ Falló la exportación de inventario Siigo.\n\nError: {$e->getMessage()}",
            fn ($msg) => $msg
                ->to($this->notifyEmail)
                ->subject("Error en exportación Siigo")
        );
    }
}

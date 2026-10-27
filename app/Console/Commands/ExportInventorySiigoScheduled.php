<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DispatchesSiigoExport;
use App\Jobs\ExportInventorySiigoJob;

class ExportInventorySiigoScheduled extends DispatchesSiigoExport
{
    protected $signature = 'siigo:export-inventory-scheduled {--email=* : Enviar el reporte solo a este correo (se puede repetir)}';

    protected $description = 'Ejecuta la exportación automática de inventario Siigo (cron)';

    protected function job(): string
    {
        return ExportInventorySiigoJob::class;
    }

    protected function emails(): array
    {
        return config('siigo.mail.inventory');
    }

    /** Se despacha un job por cada signo de inventario (positivo y negativo). */
    protected function filters(): array
    {
        return ['page_size' => 100, 'type' => 'Product'];
    }

    public function handle(): int
    {
        $recipients = $this->recipients();

        if ($recipients === null) {
            return self::FAILURE;
        }

        foreach ([true, false] as $positive) {
            $this->job()::dispatch([...$this->filters(), 'positive' => $positive], $recipients);
        }

        $this->info('✅ Jobs de exportación de inventario (positivo y negativo) despachados.');
        $this->line('Destinatarios: ' . implode(', ', $recipients));

        return self::SUCCESS;
    }
}

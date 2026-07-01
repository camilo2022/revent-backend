<?php

namespace App\Console\Commands;

use App\Jobs\ExportInventorySiigoJob;
use Illuminate\Console\Command;

class ExportInventorySiigoScheduled extends Command
{
    protected $signature = 'siigo:export-inventory-scheduled';

    protected $description = 'Ejecuta la exportación automática de inventario Siigo (cron)';

    public function handle(): int
    {
        $emails = [
            'operaciones@revent.com.co',
            'ingenieria@revent.com.co',
            'leanmanagement@revent.com.co',
            'camiloacacio16@gmail.com',
        ];

        $baseFilters = [
            'created_start' => null,
            'created_end' => null,
            'page_size' => 100,
            'type' => 'Product',
        ];

        foreach ([true, false] as $positive) {
            ExportInventorySiigoJob::dispatch(
                [...$baseFilters, 'positive' => $positive],
                $emails
            );
        }

        return self::SUCCESS;
    }
}

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
        $filters = [
            'created_start' => null,
            'created_end' => null,
            'page_size' => 100,
            'type' => 'Product',
            'positive' => true,
        ];

        ExportInventorySiigoJob::dispatch(
            $filters,
            true,
            ['operaciones@revent.com.co', 'camiloacacio16@gmail.com']
        );

        $this->info('✅ Job de exportación Siigo despachado correctamente.');

        return self::SUCCESS;
    }
}

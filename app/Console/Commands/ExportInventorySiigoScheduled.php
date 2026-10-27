<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DispatchesSiigoExport;
use App\Jobs\ExportInventorySiigoJob;

class ExportInventorySiigoScheduled extends DispatchesSiigoExport
{
    protected $signature = 'siigo:export-inventory-scheduled';

    protected $description = 'Ejecuta la exportación automática de inventario Siigo (cron)';

    protected function job(): string
    {
        return ExportInventorySiigoJob::class;
    }

    protected function emails(): array
    {
        return config('siigo.mail.inventory');
    }

    /** Un solo Excel con las hojas de inventario positivo y negativo. */
    protected function filters(): array
    {
        return ['page_size' => 100, 'type' => 'Product', 'types' => ['positive', 'negative']];
    }
}

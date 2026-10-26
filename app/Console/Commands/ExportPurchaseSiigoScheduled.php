<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DispatchesSiigoExport;
use App\Jobs\ExportPurchaseSiigoJob;

class ExportPurchaseSiigoScheduled extends DispatchesSiigoExport
{
    protected $signature = 'siigo:export-purchase-scheduled';

    protected $description = 'Ejecuta la exportación automática de compras Siigo del mes en curso (cron)';

    protected string $successMessage = '✅ Job de exportación de compras Siigo despachado correctamente.';

    protected function job(): string
    {
        return ExportPurchaseSiigoJob::class;
    }
}

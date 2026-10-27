<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DispatchesSiigoExport;
use App\Jobs\ExportInvoiceSiigoJob;

class ExportInvoiceSiigoScheduled extends DispatchesSiigoExport
{
    protected $signature = 'siigo:export-invoice-scheduled';

    protected $description = 'Ejecuta la exportación automática de facturas Siigo del mes en curso (cron)';

    protected string $successMessage = '✅ Job de exportación de facturas Siigo despachado correctamente.';

    protected function job(): string
    {
        return ExportInvoiceSiigoJob::class;
    }
}

<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DispatchesSiigoExport;
use App\Jobs\ExportInvoice360SiigoJob;

class ExportInvoice360SiigoScheduled extends DispatchesSiigoExport
{
    protected $signature = 'siigo:export-invoice-360-scheduled';

    protected $description = 'Ejecuta la exportación automática de facturas 360 Siigo del mes en curso (cron)';

    protected string $successMessage = '✅ Job de exportación de facturas 360 Siigo despachado correctamente.';

    protected function job(): string
    {
        return ExportInvoice360SiigoJob::class;
    }
}

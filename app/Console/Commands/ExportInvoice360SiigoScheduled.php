<?php

namespace App\Console\Commands;

use App\Jobs\ExportInvoice360SiigoJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExportInvoice360SiigoScheduled extends Command
{
    protected $signature = 'siigo:export-invoice-360-scheduled';

    protected $description = 'Ejecuta la exportación automática de facturas 360 Siigo del mes en curso (cron)';

    public function handle(): int
    {
        $emails = [
            'operaciones@revent.com.co',
            'ingenieria@revent.com.co',
            'leanmanagement@revent.com.co',
            'tecnologia@revent.com.co'
        ];

        $date = Carbon::now();

        $filters = [
            'created_start' => $date->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s'),
            'created_end' => $date->copy()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s'),
            'page_size' => 100,
        ];

        ExportInvoice360SiigoJob::dispatch(
            $filters,
            $emails
        );

        $this->info('✅ Job de exportación de facturas 360 Siigo despachado correctamente.');

        return self::SUCCESS;
    }
}

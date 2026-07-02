<?php

namespace App\Console\Commands;

use App\Jobs\ExportInvoiceSiigoJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExportInvoiceSiigoScheduled extends Command
{
    protected $signature = 'siigo:export-invoice-scheduled';

    protected $description = 'Ejecuta la exportación automática de facturas Siigo del mes anterior (cron)';

    public function handle(): int
    {
        $emails = [
            'operaciones@revent.com.co',
            'ingenieria@revent.com.co',
            'leanmanagement@revent.com.co',
            'camiloacacio16@gmail.com',
        ];

        $date = Carbon::now();

        $filters = [
            'created_start' => $date->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s'),
            'created_end' => $date->copy()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s'),
            'page_size' => 100,
        ];

        ExportInvoiceSiigoJob::dispatch(
            $filters,
            $emails
        );

        $this->info('✅ Job de exportación de facturas Siigo despachado correctamente.');

        return self::SUCCESS;
    }
}

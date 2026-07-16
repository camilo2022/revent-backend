<?php

namespace App\Console\Commands;

use App\Jobs\ExportPurchaseSiigoJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExportPurchaseSiigoScheduled extends Command
{
    protected $signature = 'siigo:export-purchase-scheduled';

    protected $description = 'Ejecuta la exportación automática de compras Siigo del mes en curso (cron)';

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

        ExportPurchaseSiigoJob::dispatch(
            $filters,
            $emails
        );

        $this->info('✅ Job de exportación de compras Siigo despachado correctamente.');

        return self::SUCCESS;
    }
}

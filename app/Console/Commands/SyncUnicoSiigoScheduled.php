<?php

namespace App\Console\Commands;

use App\Jobs\ExportPurchaseSiigoJob;
use App\Jobs\SyncUnicoSiigoJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExportPurchaseSiigoScheduled extends Command
{
    protected $signature = 'siigo:sync-unico-scheduled';

    protected $description = 'Ejecuta la sincronización automática de facturas Siigo del mes en curso (cron)';

    public function handle(): int
    {
        $date = Carbon::now();

        SyncUnicoSiigoJob::dispatch([
            'created_start' => $date->copy()->startOfDay()->format('Y-m-d H:i:s'),
            'created_end' => $date->copy()->endOfDay()->format('Y-m-d H:i:s'),
            'page' => 1,
            'page_size' => 100
        ]);

        $this->info('✅ Job de sincronización de facturas Siigo con unico despachado correctamente.');

        return self::SUCCESS;
    }
}

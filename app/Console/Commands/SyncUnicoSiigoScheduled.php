<?php

namespace App\Console\Commands;

use App\Jobs\SyncUnicoSiigoJob;
use App\Services\Siigo\SyncUnicoRunLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncUnicoSiigoScheduled extends Command
{
    protected $signature = 'siigo:sync-unico-scheduled';

    protected $description = 'Ejecuta la sincronización automática de facturas Siigo del mes en curso (cron)';

    public function handle(SyncUnicoRunLog $log): int
    {
        $date = Carbon::now();

        $start = $date->copy()->startOfDay()->format('Y-m-d H:i:s');
        $end = $date->copy()->endOfDay()->format('Y-m-d H:i:s');

        SyncUnicoSiigoJob::dispatch([
            'created_start' => $start,
            'created_end' => $end,
            'page' => 1,
            'page_size' => 100,
            'source' => SyncUnicoRunLog::AUTOMATIC,
            'run_id' => $log->start(SyncUnicoRunLog::AUTOMATIC, $start, $end),
        ]);

        $this->info('✅ Job de sincronización de facturas Siigo con unico despachado correctamente.');

        return self::SUCCESS;
    }
}

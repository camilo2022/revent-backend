<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('siigo:export-inventory-scheduled')->dailyAt('07:00');
Schedule::command('siigo:export-inventory-scheduled')->dailyAt('13:00');
Schedule::command('siigo:export-inventory-scheduled')->dailyAt('21:09');

//Schedule::command('siigo:export-inventory-scheduled')->dailyAt('14:00');

/*Schedule::call(function () {

    $createdStart = Carbon::now()->subMinutes(20)->format('Y-m-d H:i:s');

    Log::info('Iniciando sincronización Siigo', [
        'created_start' => $createdStart,
        'executed_at' => now()->format('Y-m-d H:i:s')
    ]);

    try {
        app(SiigoController::class)->execute([
            'created_start' => $createdStart
        ]);
        Log::info('Sincronización Siigo finalizada correctamente', [
            'created_start' => $createdStart
        ]);
    } catch (\Throwable $e) {
        Log::error('Error en sincronización Siigo', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'created_start' => $createdStart
        ]);
        throw $e;
    }
})->everyFifteenMinutes();*/

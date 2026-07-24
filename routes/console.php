<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('siigo:export-invoice-scheduled')->dailyAt('06:15');

Schedule::command('siigo:export-invoice-360-scheduled')->dailyAt('06:30');

Schedule::command('siigo:export-purchase-scheduled')->dailyAt('06:45');

Schedule::command('siigo:export-inventory-scheduled')->dailyAt('07:00');

Schedule::command('siigo:sync-unico-scheduled')->hourly()->between('08:00', '22:00');

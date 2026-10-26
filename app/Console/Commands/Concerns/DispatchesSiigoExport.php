<?php

namespace App\Console\Commands\Concerns;

use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Base de los comandos programados de exporte Siigo. Cada subclase define
 * $signature, $description, $successMessage y job(). Los destinatarios salen de config('siigo.mail.*').
 */
abstract class DispatchesSiigoExport extends Command
{
    protected string $successMessage = '';

    /** Clase del job a despachar. */
    abstract protected function job(): string;

    /** Destinatarios por defecto del exporte. */
    protected function emails(): array
    {
        return config('siigo.mail.exports');
    }

    /** Filtros del job: por defecto, el mes en curso. */
    protected function filters(): array
    {
        $date = Carbon::now();

        return [
            'created_start' => $date->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s'),
            'created_end' => $date->copy()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s'),
            'page_size' => 100,
        ];
    }

    public function handle(): int
    {
        $this->job()::dispatch($this->filters(), $this->emails());

        if ($this->successMessage !== '') {
            $this->info($this->successMessage);
        }

        return self::SUCCESS;
    }
}

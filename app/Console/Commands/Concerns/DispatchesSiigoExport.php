<?php

namespace App\Console\Commands\Concerns;

use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Base de los comandos programados de exporte Siigo. Cada subclase define
 * $signature, $description, $successMessage y job(). Los destinatarios salen de config('siigo.mail.*'),
 * salvo que se pase --email=correo (útil para probar un reporte sin enviarlo a toda la lista).
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

    /**
     * Destinatarios efectivos: los de --email si se indicaron, o los de la configuración.
     *
     * @return array<int,string>|null null si algún correo de --email no es válido
     */
    protected function recipients(): ?array
    {
        $custom = $this->hasOption('email') ? array_filter((array) $this->option('email')) : [];

        if (!$custom) {
            return $this->emails();
        }

        foreach ($custom as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error("El correo «{$email}» no es válido.");

                return null;
            }
        }

        return array_values($custom);
    }

    public function handle(): int
    {
        $recipients = $this->recipients();

        if ($recipients === null) {
            return self::FAILURE;
        }

        $this->job()::dispatch($this->filters(), $recipients);
        $this->line('Destinatarios: ' . implode(', ', $recipients));

        if ($this->successMessage !== '') {
            $this->info($this->successMessage);
        }

        return self::SUCCESS;
    }
}

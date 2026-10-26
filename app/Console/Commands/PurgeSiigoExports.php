<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeSiigoExports extends Command
{
    protected $signature = 'siigo:purge-exports {--days= : Días de retención (por defecto config siigo.exports.retention_days)}';

    protected $description = 'Borra del disco público los exportes Siigo (exports/) con más de N días';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('siigo.exports.retention_days', 7));
        $limit = now()->subDays($days)->getTimestamp();
        $disk = Storage::disk('public');
        $deleted = 0;

        foreach ($disk->files('exports') as $file) {
            if ($disk->lastModified($file) < $limit) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Exportes eliminados: {$deleted} (más de {$days} días).");

        return self::SUCCESS;
    }
}

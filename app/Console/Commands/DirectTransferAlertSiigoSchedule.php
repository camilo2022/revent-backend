<?php

namespace App\Console\Commands;

use App\Jobs\DirectTransferAlertSiigoJob;
use App\Jobs\ImportMasiveTransferSiigoJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DirectTransferAlertSiigoSchedule extends Command
{
    protected $signature = 'siigo:direct-transfer-alert {--warehouse= : Solo esta bodega (id)} {--date= : Día a revisar (Y-m-d); por defecto ayer}';

    protected $description = 'Avisa por correo los traslados directos del día anterior de cada bodega de tienda (un job por bodega)';

    /** Segundos entre el inicio de cada bodega, para no golpear a Siigo de golpe. */
    private const STAGGER_SECONDS = 20;

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();

        // Bodegas de tienda: las mapeadas en la carga masiva de traslados que tienen bodega de tránsito.
        $stores = collect((new \ReflectionClassConstant(ImportMasiveTransferSiigoJob::class, 'BODEGAS'))->getValue()['DIRECTO'])
            ->filter(fn ($bodega) => !empty($bodega['transito']))
            ->keys()
            ->map(fn ($id) => (int) $id);

        $ids = $this->option('warehouse') ? [(int) $this->option('warehouse')] : $stores->all();

        foreach ($ids as $i => $id) {
            DirectTransferAlertSiigoJob::dispatch($id, $date->toDateString())->delay(now()->addSeconds($i * self::STAGGER_SECONDS));
        }

        $this->info('Revisión de traslados directos del ' . $date->format('d/m/Y') . ': ' . count($ids) . ' bodegas despachadas.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Exports\InventorySiigoMultiSheetExport;
use App\Jobs\Concerns\SendsExportMail;
use App\Mail\ExportInventorySiigoMail;
use App\Services\Siigo\SiigoInventoryRowsService;
use App\Services\SiigoInventoryService;

/**
 * Reporte de inventarios en un solo Excel: una hoja de inventario positivo y otra de negativo.
 * filters['types'] limita las hojas (['positive'], ['negative']); por defecto salen las dos.
 */
class ExportInventorySiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use SendsExportMail;

    /** Nombre de cada hoja y del archivo cuando solo lleva una. */
    private const SHEETS = [
        SiigoInventoryRowsService::POSITIVE => 'inventarios_con_ingreso',
        SiigoInventoryRowsService::NEGATIVE => 'inventarios_por_ingreso',
    ];

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(private array $filters, private string|array $notifyEmail)
    {
        $this->onQueue('reports');
    }

    public function handle(SiigoInventoryRowsService $rows): void
    {
        ini_set('memory_limit', '-1');

        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $signs = $this->signs();

        $data = $rows->build($token, $siigo->stores(), $signs);

        $sheets = [];

        foreach ($signs as $sign) {
            $sheets[self::SHEETS[$sign]] = $data[$sign];
        }

        $name = count($signs) === 1 ? self::SHEETS[$signs[0]] : 'inventarios';

        $this->storeAndNotify(
            new InventorySiigoMultiSheetExport($sheets),
            $this->exportFilename($name, 'Ymd_His')
        );
    }

    /** Tipos de inventario del reporte. Acepta el filtro antiguo 'positive' de jobs ya encolados. */
    private function signs(): array
    {
        if (isset($this->filters['positive']) && !isset($this->filters['types'])) {
            return [$this->filters['positive'] ? SiigoInventoryRowsService::POSITIVE : SiigoInventoryRowsService::NEGATIVE];
        }

        $types = array_values(array_intersect(
            [SiigoInventoryRowsService::POSITIVE, SiigoInventoryRowsService::NEGATIVE],
            $this->filters['types'] ?? [SiigoInventoryRowsService::POSITIVE, SiigoInventoryRowsService::NEGATIVE]
        ));

        return $types ?: [SiigoInventoryRowsService::POSITIVE, SiigoInventoryRowsService::NEGATIVE];
    }

    protected function mailClass(): string
    {
        return ExportInventorySiigoMail::class;
    }
}

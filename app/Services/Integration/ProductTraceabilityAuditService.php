<?php

namespace App\Services\Integration;

use App\Imports\ProductTraceabilitySiigoImport;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoTransitAuditService;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Trazabilidad de productos con auditoría de tránsitos: lee los códigos del Excel y arma la auditoría de la bodega.
 * Lo comparten la consulta (JSON) y la descarga (xlsx) para no duplicar el proceso.
 *
 * Copiado de Integration\ProductTraceabilitySiigoController::audit.
 */
class ProductTraceabilityAuditService
{
    public function __construct(private SiigoClient $client, private SiigoTransitAuditService $transitAudit)
    {
    }

    /** Códigos de la columna "codigo" del archivo (en mayúsculas, sin repetir). */
    public function codesFromFile(UploadedFile $file): array
    {
        return Excel::toCollection(new ProductTraceabilitySiigoImport, $file)
            ->first()
            ->pluck('codigo')
            ->filter()
            ->map(fn ($codigo) => strtoupper(trim($codigo)))
            ->unique()
            ->values()
            ->all();
    }

    /** Auditoría de la bodega con los códigos del archivo. */
    public function audit(int $warehouseId, string $startDate, array $codes, array $warehouses): array
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        return $this->transitAudit->audit($this->client->token(), $warehouseId, $startDate, $codes, $warehouses);
    }
}

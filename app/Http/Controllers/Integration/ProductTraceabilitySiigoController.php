<?php

namespace App\Http\Controllers\Integration;

use App\Exports\ProductTraceabilityAuditExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\ProductTraceability\DownloadRequest;
use App\Http\Requests\Integration\ProductTraceability\SearchRequest;
use App\Imports\ProductTraceabilitySiigoImport;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoTransitAuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductTraceabilitySiigoController extends Controller
{
    private const AUDIT_CACHE_PREFIX = 'siigo.traceability_audit.';

    private const EXCEL_NAME = 'TRAZABILIDAD DE PRODUCTOS.xlsx';

    public function __construct(
        private SiigoClient $client,
        private SiigoCatalogService $catalog,
        private SiigoTransitAuditService $transitAudit
    ) {
    }

    public function product_traceability()
    {
        // Solo las bodegas "reales": cada una lleva marcado si tiene su bodega TRANSITO, que la auditoría revisa sola.
        $warehouses = collect($this->catalog->warehousesWithTransit($this->client->token()))
            ->reject(fn ($warehouse) => (int) $warehouse['id'] === -1)
            ->values()
            ->all();

        return view('integration.product_traceability.index', compact('warehouses'));
    }

    /** Pantalla: trazabilidad con saldo acumulado y auditoría de tránsitos. */
    public function product_traceability_search(SearchRequest $request)
    {
        $audit = $this->audit($request);

        if ($audit === null) {
            return $this->withoutCodes();
        }

        // Se guarda el resultado un rato para poder bajar el mismo contenido en Excel sin volver a consultar Siigo.
        $key = (string) Str::uuid();
        Cache::put(self::AUDIT_CACHE_PREFIX . $key, $audit, now()->addHour());

        return view('integration.product_traceability.result', compact('audit', 'key'));
    }

    /** Descarga directa desde el formulario: el mismo Excel que se baja desde la pantalla. */
    public function product_traceability_download(DownloadRequest $request)
    {
        $audit = $this->audit($request);

        if ($audit === null) {
            return $this->withoutCodes();
        }

        return Excel::download(new ProductTraceabilityAuditExport($audit), self::EXCEL_NAME);
    }

    /** Excel con lo mismo que muestra la pantalla (resultado guardado una hora). */
    public function product_traceability_excel(string $key)
    {
        $audit = Cache::get(self::AUDIT_CACHE_PREFIX . $key);

        if (!$audit) {
            return redirect()
                ->route('siigo.product_traceability')
                ->withErrors(['file' => 'La consulta venció (se guarda una hora). Vuelve a ejecutarla para descargar el Excel.']);
        }

        return Excel::download(new ProductTraceabilityAuditExport($audit), self::EXCEL_NAME);
    }

    /** Auditoría de la bodega con los códigos del archivo; null si el archivo no trae códigos. */
    private function audit(DownloadRequest $request): ?array
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $codigos = Excel::toCollection(new ProductTraceabilitySiigoImport, $request->file('file'))
            ->first()
            ->pluck('codigo')
            ->filter()
            ->map(fn ($codigo) => strtoupper(trim($codigo)))
            ->unique()
            ->values()
            ->all();

        if (!$codigos) {
            return null;
        }

        return $this->transitAudit->audit(
            $this->client->token(),
            $request->integer('warehouse_id'),
            $request->input('start_date'),
            $codigos,
            $request->warehouses()
        );
    }

    private function withoutCodes()
    {
        return back()->withErrors(['file' => 'El archivo no tiene códigos en la columna "codigo".'])->withInput();
    }
}

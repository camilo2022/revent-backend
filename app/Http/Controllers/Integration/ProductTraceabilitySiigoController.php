<?php

namespace App\Http\Controllers\Integration;

use App\Exports\ProductTraceabilitySiigoExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\ProductTraceability\DownloadRequest;
use App\Http\Requests\Integration\ProductTraceability\SearchRequest;
use App\Imports\ProductTraceabilitySiigoImport;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoLinks;
use App\Services\Siigo\SiigoTraceabilityService;
use App\Services\Siigo\SiigoTransitAuditService;
use Maatwebsite\Excel\Facades\Excel;

class ProductTraceabilitySiigoController extends Controller
{
    public function __construct(
        private SiigoClient $client,
        private SiigoCatalogService $catalog,
        private SiigoTraceabilityService $traceability,
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

    /** Pantalla de auditoría: ¿los ingresos por traslado pasaron antes por la bodega de tránsito? */
    public function product_traceability_search(SearchRequest $request)
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
            return back()->withErrors(['file' => 'El archivo no tiene códigos en la columna "codigo".'])->withInput();
        }

        $audit = $this->transitAudit->audit(
            $this->client->token(),
            $request->integer('warehouse_id'),
            $request->input('start_date'),
            $codigos,
            $request->warehouses()
        );

        return view('integration.product_traceability.result', compact('audit'));
    }

    public function product_traceability_download(DownloadRequest $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $token = $this->client->token();
        $warehouses = $request->warehouses();

        $codigos = Excel::toCollection(new ProductTraceabilitySiigoImport, $request->file('file'))->first()->map(function ($item) {
                $item['codigo'] = strtoupper($item['codigo']);
                return $item;
            })->pluck('codigo')->unique()->values()->all();

        $data = $this->movimientos_productos_bodegas($token, $request->integer('warehouse_id'), $request->input('start_date'), $warehouses);

        $data = collect($data)->unique()->map(function ($item) {
                $item = (object) $item;
                $url_hyperlink = '';

                if(str_starts_with($item->Voucher, 'FV-')){
                    $url_hyperlink = SiigoLinks::salesInvoice($item->VoucherID);
                } else if (str_starts_with($item->Voucher, 'FC-')){
                    $url_hyperlink = SiigoLinks::purchaseInvoice($item->VoucherID);
                } else if (str_starts_with($item->Voucher, 'NT-')){
                    $url_hyperlink = SiigoLinks::transferNote($item->VoucherID);
                } else if (str_starts_with($item->Voucher, 'A-')){
                    $url_hyperlink = SiigoLinks::inventoryEntry($item->VoucherID);
                } else if (str_starts_with($item->Voucher, 'NC-')){
                    $url_hyperlink = SiigoLinks::creditNote($item->VoucherID);
                }
                $item->UrlHyperLink = $url_hyperlink;
                return $item;
            })->groupBy('ProductCode');

        return Excel::download(new ProductTraceabilitySiigoExport($codigos, $data), "TRAZABILIDAD DE PRODUCTOS.xlsx");
    }

    private function movimientos_productos_bodegas(string $token, int $warehouseId, string $startDate, array $warehouses)
    {
        return $this->traceability->movements($token, $warehouseId, $startDate, $warehouses);
    }
}

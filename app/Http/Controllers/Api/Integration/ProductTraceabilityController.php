<?php

namespace App\Http\Controllers\Api\Integration;

use App\Exports\ProductTraceabilityAuditExport;
use App\Http\Controllers\Api\Integration\Concerns\RespondsWithFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\ProductTraceability\DownloadRequest;
use App\Http\Requests\Api\Integration\ProductTraceability\FormatRequest;
use App\Http\Requests\Api\Integration\ProductTraceability\SearchRequest;
use App\Http\Requests\Api\Integration\ProductTraceability\WarehousesRequest;
use App\Http\Resources\Api\Integration\ProductTraceability\TraceabilityAuditResource;
use App\Http\Resources\Api\Integration\ProductTraceability\WarehouseCollection;
use App\Services\Integration\ProductTraceabilityAuditService;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Support\Integration\WarehouseAccess;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;

/** Trazabilidad de productos de una bodega con saldo acumulado y auditoría de tránsitos. */
class ProductTraceabilityController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure, RespondsWithFile;

    private const EXCEL_NAME = 'TRAZABILIDAD DE PRODUCTOS';

    private const TEMPLATE = 'storage/formats/formato_trazabilidad_producto.xlsx';

    public function __construct(
        private SiigoClient $client,
        private SiigoCatalogService $catalog,
        private ProductTraceabilityAuditService $audit
    ) {
    }

    /** Solo las bodegas "reales" a las que el usuario tiene acceso; cada una lleva marcado si tiene bodega TRANSITO. */
    public function warehouses(WarehousesRequest $request)
    {
        try {
            $warehouses = collect($this->catalog->warehousesWithTransit($this->client->token()))
                ->reject(fn ($warehouse) => (int) $warehouse['id'] === -1)
                ->values()
                ->all();

            return $this->successResponse(
                new WarehouseCollection(WarehouseAccess::filter($warehouses, Auth::user())),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Plantilla de Excel con la columna "codigo". */
    public function format(FormatRequest $request)
    {
        try {
            $path = public_path(self::TEMPLATE);

            if (!is_file($path)) {
                throw new \DomainException('No se encontró la plantilla de trazabilidad.');
            }

            return response()->download($path, 'formato_trazabilidad_producto.xlsx');
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function search(SearchRequest $request)
    {
        try {
            return $this->successResponse(
                new TraceabilityAuditResource($this->runAudit($request)),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function download(DownloadRequest $request)
    {
        try {
            return $this->downloadExcel(new ProductTraceabilityAuditExport($this->runAudit($request)), self::EXCEL_NAME);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    private function runAudit(DownloadRequest $request): array
    {
        return $this->audit->audit(
            $request->integer('warehouse_id'),
            $request->input('start_date'),
            $request->codes(),
            $request->warehouses()
        );
    }
}

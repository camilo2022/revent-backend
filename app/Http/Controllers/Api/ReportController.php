<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Exports\ReportExport;
use App\Http\Requests\Report\ReportAllRequest;
use App\Http\Requests\Report\ReportExportRequest;
use App\Http\Requests\Report\ReportFindRequest;
use App\Http\Requests\Report\ReportGenerateRequest;
use App\Http\Resources\Report\ReportCollection;
use App\Http\Resources\Report\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    public function __construct(private ReportService $service)
    {
    }

    /**
     * @OA\Get(
     *     path="/reports/all",
     *     tags={"Reports"},
     *     summary="Listar reportes disponibles",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, description="Filtro de búsqueda", @OA\Schema(type="string")),
     *     @OA\Parameter(name="column", in="query", required=false, description="Columna a ordenar", @OA\Schema(type="string", enum={"id","name","created_at"})),
     *     @OA\Parameter(name="dir", in="query", required=false, description="Orden de datos", @OA\Schema(type="string", enum={"asc","desc"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="with_trashed", in="query", required=false, description="Registros inactivos", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Lista de reportes cargada correctamente"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function all(ReportAllRequest $request)
    {
        try {
            $reports = Report::when($request->filled('search'), fn ($query) => $query->search($request->input('search')))
                ->when($request->boolean('with_trashed'), fn ($query) => $query->withTrashed())
                ->when(
                    $request->filled('column') && $request->filled('dir'),
                    fn ($query) => $query->orderBy($request->input('column'), $request->input('dir')),
                    fn ($query) => $query->orderBy('name')
                );

            $reports = $reports->paginate($request->integer('per_page', $reports->count()));

            return $this->successResponse(new ReportCollection($reports), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/reports/find/{id}",
     *     tags={"Reports"},
     *     summary="Consultar un reporte (campos y filtros disponibles)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del reporte", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Reporte cargado correctamente"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function find(ReportFindRequest $request, $id)
    {
        try {
            $report = Report::findOrFail($id);

            return $this->successResponse(['report' => ReportResource::map($report)], $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/reports/generate/{id}",
     *     tags={"Reports"},
     *     summary="Generar un reporte con los campos y filtros elegidos",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del reporte", @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"fields"},
     *         @OA\Property(property="fields", type="array", description="data_key de los campos elegidos", @OA\Items(type="string", example="code")),
     *         @OA\Property(property="filters", type="object", description="Filtros por clave: lista de valores (select) o {from, to} (date_range)")
     *     )),
     *     @OA\Response(response=200, description="Reporte generado: columnas y filas"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function generate(ReportGenerateRequest $request, $id)
    {
        try {
            $report = Report::findOrFail($id);
            $result = $this->service->run($report, $request->input('fields', []), $request->input('filters', []), $request->boolean('pivot'));

            return $this->successResponse($result, $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/reports/export/{id}",
     *     tags={"Reports"},
     *     summary="Exportar un reporte a Excel",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del reporte", @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"fields"},
     *         @OA\Property(property="fields", type="array", @OA\Items(type="string", example="code")),
     *         @OA\Property(property="filters", type="object")
     *     )),
     *     @OA\Response(response=200, description="Archivo Excel (.xlsx)"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function export(ReportExportRequest $request, $id)
    {
        try {
            $report = Report::findOrFail($id);
            $result = $this->service->run($report, $request->input('fields', []), $request->input('filters', []), $request->boolean('pivot'));
            $name = str($report->name)->slug('_') . '_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new ReportExport($result['columns'], $result['rows']), $name);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

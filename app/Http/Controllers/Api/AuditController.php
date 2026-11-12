<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\Audit\AuditAllRequest;
use App\Http\Requests\Audit\AuditFindRequest;
use App\Http\Requests\Audit\AuditRecordRequest;
use App\Http\Resources\Audit\AuditCollection;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantity;
use App\Models\TechnicalSheet;
use OwenIt\Auditing\Models\Audit;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Audit",
 *     description="Endpoints para gestionar auditorias"
 * )
 *
 * @OA\Schema(
 *     schema="Audit",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_type", type="string", nullable=true, example=null),
 *     @OA\Property(property="event", type="string", example="attach"),
 *     @OA\Property(property="auditable_type", type="string", example="App\\Models\\Role"),
 *     @OA\Property(property="auditable_name", type="string", example="Rol"),
 *     @OA\Property(property="url", type="string", format="uri", example="/App"),
 *     @OA\Property(property="ip_address", type="string", example="127.0.0.1"),
 *     @OA\Property(property="tags", type="string", example="permission"),
 *     @OA\Property(property="audit_tag", type="string", example="Permiso"),
 *     @OA\Property(
 *         property="old_values",
 *         type="object",
 *         @OA\Property(
 *             property="permissions",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="name", type="string", example="users.all"),
 *                 @OA\Property(property="title", type="string", example="Listar usuarios"),
 *                 @OA\Property(property="description", type="string", example="Permite listar todos los usuarios del sistema.")
 *             )
 *         )
 *     ),
 *     @OA\Property(
 *         property="new_values",
 *         type="object",
 *         @OA\Property(
 *             property="permissions",
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="name", type="string", example="users.all"),
 *                 @OA\Property(property="title", type="string", example="Listar usuarios"),
 *                 @OA\Property(property="description", type="string", example="Permite listar todos los usuarios del sistema.")
 *             )
 *         )
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-04-24 22:06:56"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-24 22:06:56"),
 *     @OA\Property(
 *         property="user",
 *         nullable=true,
 *         type="object",
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="auditable",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="users"),
 *         @OA\Property(property="title", type="string", example="Usuarios"),
 *         @OA\Property(property="description", type="string", example="Gestión de usuarios."),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-04-24T22:06:56.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-24T22:06:56.000000Z")
 *     )
 * )
 */
class AuditController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/audits/all",
     *     tags={"Audits"},
     *     summary="Listar auditorias",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filtro de Busqueda.",
     *         required=false,
     *         @OA\Schema(type="string", example="juan")
     *     ),
     *     @OA\Parameter(
     *         name="column",
     *         in="query",
     *         description="Columna a ordenar.",
     *         required=false,
     *         @OA\Schema(type="string", enum={"id","name","description","created_at", "updated_at"}, example="name")
     *     ),
     *     @OA\Parameter(
     *         name="dir",
     *         in="query",
     *         description="Orden de datos.",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc","desc"}, example="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Registros por página.",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="with_trashed",
     *         in="query",
     *         description="Registros inactivos.",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de auditorias cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="audits",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Audit")
     *                 ),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(
     *                         property="pagination",
     *                         type="object",
     *                         @OA\Property(property="total", type="integer", example=1),
     *                         @OA\Property(property="count", type="integer", example=1),
     *                         @OA\Property(property="per_page", type="integer", example=1),
     *                         @OA\Property(property="current_page", type="integer", example=1),
     *                         @OA\Property(property="total_pages", type="integer", example=1)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function all(AuditAllRequest $request)
    {
        try {
            $audits = Audit::with(['user' => ['employee' => ['person', 'position' => ['area']]], 'auditable'])
                ->when($request->filled('user_id'), function ($query) use ($request) {
                    return $query->where('user_id', $request->integer('user_id'));
                })
                ->when($request->filled('event'), function ($query) use ($request) {
                    return $query->where('event', $request->input('event'));
                })
                ->when($request->filled('auditable_name'), function ($query) use ($request) {
                    return $query->where('auditable_type', $request->input('auditable_name'));
                })
                ->when($request->filled('start_date') || $request->filled('end_date'), function ($query) use ($request) {
                    return $query->whereBetween('created_at', [
                        Carbon::parse($request->input('start_date'))->startOfDay(),
                        Carbon::parse($request->input('end_date'))->endOfDay()
                    ]);
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $audits = $audits->paginate($request->integer('per_page', $audits->count()));

            return $this->successResponse(
                new AuditCollection($audits),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/audits/find/{id}",
     *     tags={"Audits"},
     *     summary="Obtener una auditoria específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la auditoria",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tono de lavado cargado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="wash_tone",
     *                     ref="#/components/schemas/Audit"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de la auditoria no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example=1)
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/UnauthorizedResponse"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/ServerErrorResponse"
     *         )
     *     )
     * )
     */
    public function find(AuditFindRequest $request, $id)
    {
        try {
            $audit = Audit::with(['user' => ['employee' => ['person', 'position' => ['area']]], 'auditable'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'audit' => $audit
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * Historial de un registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde.
     * El modelo sale de la ruta de cada módulo (/{módulo}/audits/{id}).
     */
    public function record(AuditRecordRequest $request, $id)
    {
        try {
            $targets = $this->auditTargets($request->route('model'), (int) $id);

            $audits = Audit::with(['user' => ['employee' => ['person', 'position' => ['area']]]])
                ->where(function ($query) use ($targets) {
                    foreach ($targets as [$type, $ids]) {
                        $query->orWhere(fn ($q) => $q->where('auditable_type', $type)->whereIn('auditable_id', $ids));
                    }
                })
                ->when($request->filled('event'), fn ($query) => $query->where('event', $request->input('event')))
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            $audits = $audits->paginate($request->integer('per_page', 15));

            return $this->successResponse(
                new AuditCollection($audits),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    /**
     * Registros cuyo historial se muestra junto al del registro consultado: él mismo y lo que depende de él
     * (producto: colores, tallas y ficha técnica; orden: detalles y cantidades).
     *
     * @return array<int, array{0: string, 1: array<int, int>}>
     */
    private function auditTargets(string $model, int $id): array
    {
        $targets = [[$model, [$id]]];

        if ($model === Product::class) {
            $colors = ProductDetail::where('model_type', (new Product)->getMorphClass())->where('model_id', $id)->pluck('id')->all();
            $sizes = ProductDetail::where('model_type', (new ProductDetail)->getMorphClass())->whereIn('model_id', $colors)->pluck('id')->all();
            $sheets = TechnicalSheet::withTrashed()->where('product_id', $id)->pluck('id')->all();

            $targets[] = [ProductDetail::class, array_merge($colors, $sizes)];
            $targets[] = [TechnicalSheet::class, $sheets];
        }

        if ($model === ProductionOrder::class) {
            $details = ProductionOrderDetail::where('production_order_id', $id)->pluck('id')->all();
            $quantities = ProductionOrderDetailQuantity::whereIn('production_order_detail_id', $details)->pluck('id')->all();

            $targets[] = [ProductionOrderDetail::class, $details];
            $targets[] = [ProductionOrderDetailQuantity::class, $quantities];
        }

        return $targets;
    }
}

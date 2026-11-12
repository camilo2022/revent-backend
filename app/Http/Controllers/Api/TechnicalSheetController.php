<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\TechnicalSheet\TechnicalSheetAllRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetApprovedRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetCanceledRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetFindRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetReviewedRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetStoreRequest;
use App\Http\Requests\TechnicalSheet\TechnicalSheetUpdateRequest;
use App\Http\Resources\TechnicalSheet\TechnicalSheetCollection;
use App\Http\Resources\TechnicalSheet\TechnicalSheetResource;
use App\Models\TechnicalSheet;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="TechnicalSheets",
 *     description="Fichas técnicas de los productos: una por producto, con revisión y aprobación (los cambios quedan en la auditoría)"
 * )
 *
 * @OA\Schema(
 *     schema="TechnicalSheet",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="status", type="string", enum={"Pendiente","En revision","Aprobado","Cancelado"}, example="Pendiente"),
 *     @OA\Property(property="trademark_id", type="integer", example=12),
 *     @OA\Property(property="category_id", type="integer", example=20),
 *     @OA\Property(property="subcategory_id", type="integer", example=31),
 *     @OA\Property(property="process", type="string", nullable=true, example="Corte, guarnecida y montaje."),
 *     @OA\Property(property="description", type="string", nullable=true, example="Botín clásico"),
 *     @OA\Property(property="observation", type="string", nullable=true, example=null),
 *     @OA\Property(property="specifications", type="object", example={"horma": "H-45", "suela": "Caucho"}),
 *     @OA\Property(property="approved_by", type="integer", nullable=true, example=null),
 *     @OA\Property(property="approved_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-04 10:00:00"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-04 10:00:00"),
 *     @OA\Property(property="product", type="object", nullable=true, ref="#/components/schemas/Product"),
 *     @OA\Property(property="trademark", type="object", nullable=true),
 *     @OA\Property(property="category", type="object", nullable=true),
 *     @OA\Property(property="subcategory", type="object", nullable=true)
 * )
 */
class TechnicalSheetController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    // Relaciones que necesita el recurso (el producto trae colores y tallas para armar órdenes)
    private const WITH = ['product.collection', 'product.colors.assignable', 'product.colors.sizes.assignable', 'trademark', 'category', 'subcategory'];

    /**
     * @OA\Get(
     *     path="/technical_sheets/all",
     *     tags={"TechnicalSheets"},
     *     summary="Listar fichas técnicas",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", description="Filtro de búsqueda (producto, proceso, descripción, estado).", required=false, @OA\Schema(type="string", example="ACTION")),
     *     @OA\Parameter(name="column", in="query", description="Columna a ordenar.", required=false, @OA\Schema(type="string", enum={"id","status","approved_at","created_at","updated_at"}, example="id")),
     *     @OA\Parameter(name="dir", in="query", description="Orden de datos.", required=false, @OA\Schema(type="string", enum={"asc","desc"}, example="desc")),
     *     @OA\Parameter(name="product_id", in="query", description="Solo fichas de un producto.", required=false, @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="status", in="query", description="Solo fichas en un estado.", required=false, @OA\Schema(type="string", enum={"Pendiente","En revision","Aprobado","Cancelado"}, example="Aprobado")),
     *     @OA\Parameter(name="only_approved", in="query", description="Solo fichas aprobadas (para elegir en la orden).", required=false, @OA\Schema(type="boolean", example=true)),
     *     @OA\Parameter(name="per_page", in="query", description="Registros por página. Sin enviarlo se devuelven todos.", required=false, @OA\Schema(type="integer", example=10)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de fichas técnicas cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="technical_sheets", type="array", @OA\Items(ref="#/components/schemas/TechnicalSheet")),
     *                 @OA\Property(property="meta", type="object", @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="total", type="integer", example=1),
     *                     @OA\Property(property="count", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=1),
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="total_pages", type="integer", example=1)
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function all(TechnicalSheetAllRequest $request)
    {
        try {
            $technical_sheets = TechnicalSheet::with(self::WITH)
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('product_id'), function ($query) use ($request) {
                    return $query->where('product_id', $request->integer('product_id'));
                })
                ->when($request->filled('status'), function ($query) use ($request) {
                    return $query->where('status', $request->input('status'));
                })
                ->when($request->boolean('only_approved'), function ($query) {
                    return $query->onlyApproved();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderByDesc('id');
                });

            $technical_sheets = $technical_sheets->paginate($request->integer('per_page', $technical_sheets->count()));

            return $this->successResponse(
                new TechnicalSheetCollection($technical_sheets),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/technical_sheets/find/{id}",
     *     tags={"TechnicalSheets"},
     *     summary="Obtener una ficha técnica con su producto, colores y tallas",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la ficha técnica", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Ficha técnica cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="technical_sheet", ref="#/components/schemas/TechnicalSheet")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Identificador no registrado"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function find(TechnicalSheetFindRequest $request, $id)
    {
        try {
            $technical_sheet = TechnicalSheet::with(self::WITH)->findOrFail($id);

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/technical_sheets/store",
     *     tags={"TechnicalSheets"},
     *     summary="Crear la ficha técnica de un producto",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","trademark_id","category_id","subcategory_id"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="trademark_id", type="integer", example=12),
     *             @OA\Property(property="category_id", type="integer", example=20),
     *             @OA\Property(property="subcategory_id", type="integer", example=31),
     *             @OA\Property(property="process", type="string", nullable=true, example="Corte, guarnecida y montaje."),
     *             @OA\Property(property="description", type="string", nullable=true, example="Botín clásico"),
     *             @OA\Property(property="observation", type="string", nullable=true, example=null),
     *             @OA\Property(property="specifications", type="object", example={"horma": "H-45", "suela": "Caucho"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ficha técnica creada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="technical_sheet", ref="#/components/schemas/TechnicalSheet")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación (por ejemplo, el producto ya tiene ficha)"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function store(TechnicalSheetStoreRequest $request)
    {
        try {
            $technical_sheet = DB::transaction(function () use ($request) {
                $technical_sheet = new TechnicalSheet();
                $technical_sheet->product_id = $request->integer('product_id');
                $this->fill($technical_sheet, $request);
                $technical_sheet->status = TechnicalSheet::PENDING;
                $technical_sheet->save();

                return $technical_sheet;
            });

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet->load(self::WITH)),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/technical_sheets/update/{id}",
     *     tags={"TechnicalSheets"},
     *     summary="Actualizar una ficha técnica (menos las canceladas); el cambio queda en la auditoría",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la ficha técnica", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"trademark_id","category_id","subcategory_id"},
     *             @OA\Property(property="trademark_id", type="integer", example=12),
     *             @OA\Property(property="category_id", type="integer", example=20),
     *             @OA\Property(property="subcategory_id", type="integer", example=31),
     *             @OA\Property(property="process", type="string", nullable=true, example="Corte, guarnecida y montaje."),
     *             @OA\Property(property="description", type="string", nullable=true, example="Botín clásico"),
     *             @OA\Property(property="observation", type="string", nullable=true, example=null),
     *             @OA\Property(property="specifications", type="object", example={"horma": "H-45", "suela": "Caucho"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ficha técnica actualizada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="technical_sheet", ref="#/components/schemas/TechnicalSheet")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación (por ejemplo, la ficha está cancelada)"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function update(TechnicalSheetUpdateRequest $request, $id)
    {
        try {
            $technical_sheet = DB::transaction(function () use ($request, $id) {
                $technical_sheet = TechnicalSheet::findOrFail($id);
                $this->fill($technical_sheet, $request);
                $technical_sheet->save();

                return $technical_sheet;
            });

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet->load(self::WITH)),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Patch(
     *     path="/technical_sheets/reviewed/{id}",
     *     tags={"TechnicalSheets"},
     *     summary="Enviar la ficha a revisión (Pendiente o Cancelado → En revision)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la ficha técnica", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Ficha enviada a revisión",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="technical_sheet", ref="#/components/schemas/TechnicalSheet")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="La ficha no está en un estado válido"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function reviewed(TechnicalSheetReviewedRequest $request, $id)
    {
        return $this->changeStatus($id, TechnicalSheet::REVIEW);
    }

    /**
     * @OA\Patch(
     *     path="/technical_sheets/approved/{id}",
     *     tags={"TechnicalSheets"},
     *     summary="Aprobar la ficha (Pendiente o En revision → Aprobado)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la ficha técnica", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Ficha aprobada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="technical_sheet", ref="#/components/schemas/TechnicalSheet")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="La ficha no está en un estado válido o el producto no tiene colores con tallas"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function approved(TechnicalSheetApprovedRequest $request, $id)
    {
        return $this->changeStatus($id, TechnicalSheet::APPROVED);
    }

    /**
     * @OA\Patch(
     *     path="/technical_sheets/canceled/{id}",
     *     tags={"TechnicalSheets"},
     *     summary="Cancelar la ficha (Pendiente, En revision o Aprobado → Cancelado)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la ficha técnica", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Ficha cancelada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="technical_sheet", ref="#/components/schemas/TechnicalSheet")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="La ficha no está en un estado válido"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function canceled(TechnicalSheetCanceledRequest $request, $id)
    {
        return $this->changeStatus($id, TechnicalSheet::CANCELED);
    }

    // Datos editables de la ficha (los comparten crear y actualizar)
    private function fill(TechnicalSheet $technical_sheet, $request): void
    {
        $technical_sheet->trademark_id = $request->integer('trademark_id');
        $technical_sheet->category_id = $request->integer('category_id');
        $technical_sheet->subcategory_id = $request->integer('subcategory_id');
        $technical_sheet->process = $request->input('process');
        $technical_sheet->description = $request->input('description');
        $technical_sheet->observation = $request->input('observation');
        $technical_sheet->specifications = $request->input('specifications', []);
    }

    // Cambia el estado; al aprobar guarda quién y cuándo
    private function changeStatus($id, string $status)
    {
        try {
            $technical_sheet = DB::transaction(function () use ($id, $status) {
                $technical_sheet = TechnicalSheet::findOrFail($id);
                $technical_sheet->status = $status;

                if ($status === TechnicalSheet::APPROVED) {
                    $technical_sheet->approved_by = Auth::id();
                    $technical_sheet->approved_at = now();
                }

                $technical_sheet->save();

                return $technical_sheet;
            });

            return $this->successResponse(
                new TechnicalSheetResource($technical_sheet->load(self::WITH)),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

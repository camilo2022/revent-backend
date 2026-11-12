<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\Collection\CollectionAllRequest;
use App\Http\Requests\Collection\CollectionDeleteRequest;
use App\Http\Requests\Collection\CollectionFindRequest;
use App\Http\Requests\Collection\CollectionRestoreRequest;
use App\Http\Requests\Collection\CollectionStoreRequest;
use App\Http\Requests\Collection\CollectionUpdateRequest;
use App\Http\Resources\Collection\CollectionCollection;
use App\Http\Resources\Collection\CollectionResource;
use App\Models\Collection;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Collections",
 *     description="Colecciones del año: su año y código forman parte del código de cada producto"
 * )
 *
 * @OA\Schema(
 *     schema="Collection",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="COLECCION 5"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Colección de fin de año"),
 *     @OA\Property(property="year", type="string", example="26"),
 *     @OA\Property(property="code", type="string", example="5"),
 *     @OA\Property(property="settings", type="object", example={"year": "26", "code": "5", "last_number": 0}),
 *     @OA\Property(property="last_number", type="integer", example=0),
 *     @OA\Property(property="product_prefix", type="string", example="RB265"),
 *     @OA\Property(property="next_code", type="string", example="RB265001"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-04 10:00:00"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-04 10:00:00"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 * )
 */
class CollectionController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    /**
     * @OA\Get(
     *     path="/collections/all",
     *     tags={"Collections"},
     *     summary="Listar colecciones",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", description="Filtro de búsqueda.", required=false, @OA\Schema(type="string", example="fin de año")),
     *     @OA\Parameter(name="column", in="query", description="Columna a ordenar.", required=false, @OA\Schema(type="string", enum={"id","name","description","created_at","updated_at","deleted_at"}, example="name")),
     *     @OA\Parameter(name="dir", in="query", description="Orden de datos.", required=false, @OA\Schema(type="string", enum={"asc","desc"}, example="desc")),
     *     @OA\Parameter(name="per_page", in="query", description="Registros por página. Sin enviarlo se devuelven todos.", required=false, @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="with_trashed", in="query", description="Registros inactivos.", required=true, @OA\Schema(type="boolean", example=false)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de colecciones cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="collections", type="array", @OA\Items(ref="#/components/schemas/Collection")),
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
    public function all(CollectionAllRequest $request)
    {
        try {
            $collections = Collection::when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderByDesc('settings->year')->orderBy('settings->code');
                });

            $collections = $collections->paginate($request->integer('per_page', $collections->count()));

            return $this->successResponse(
                new CollectionCollection($collections),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/collections/find/{id}",
     *     tags={"Collections"},
     *     summary="Obtener una colección",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la colección", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Colección cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="collection", ref="#/components/schemas/Collection")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Identificador no registrado"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function find(CollectionFindRequest $request, $id)
    {
        try {
            $collection = Collection::findOrFail($id);

            return $this->successResponse(
                new CollectionResource($collection),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/collections/store",
     *     tags={"Collections"},
     *     summary="Crear una colección",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","settings"},
     *             @OA\Property(property="name", type="string", example="COLECCION 5"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Colección de fin de año"),
     *             @OA\Property(property="settings", type="object",
     *                 @OA\Property(property="year", type="string", example="26"),
     *                 @OA\Property(property="code", type="string", example="5")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Colección creada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="collection", ref="#/components/schemas/Collection")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function store(CollectionStoreRequest $request)
    {
        try {
            $collection = DB::transaction(function () use ($request) {
                $collection = new Collection();
                $collection->name = $request->input('name');
                $collection->description = $request->input('description');
                $collection->settings = (object) ['year' => $request->input('settings.year'), 'code' => $request->input('settings.code'), 'last_number' => 0];
                $collection->save();

                return $collection;
            });

            return $this->successResponse(
                new CollectionResource($collection),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/collections/update/{id}",
     *     tags={"Collections"},
     *     summary="Actualizar una colección",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la colección", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","settings"},
     *             @OA\Property(property="name", type="string", example="COLECCION 5"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Colección de fin de año"),
     *             @OA\Property(property="settings", type="object",
     *                 @OA\Property(property="year", type="string", example="26"),
     *                 @OA\Property(property="code", type="string", example="5")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Colección actualizada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="collection", ref="#/components/schemas/Collection")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function update(CollectionUpdateRequest $request, $id)
    {
        try {
            $collection = DB::transaction(function () use ($request, $id) {
                $collection = Collection::findOrFail($id);
                $collection->name = $request->input('name');
                $collection->description = $request->input('description');
                // El consecutivo de productos se conserva
                $collection->settings = (object) ['year' => $request->input('settings.year'), 'code' => $request->input('settings.code'), 'last_number' => $collection->last_number];
                $collection->save();

                return $collection;
            });

            return $this->successResponse(
                new CollectionResource($collection),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/collections/delete/{id}",
     *     tags={"Collections"},
     *     summary="Eliminar (inactivar) una colección sin productos",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la colección", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Colección eliminada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="collection", ref="#/components/schemas/Collection")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="La colección no existe o tiene productos"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function delete(CollectionDeleteRequest $request, $id)
    {
        try {
            $collection = Collection::findOrFail($id);
            $collection->delete();

            return $this->successResponse(
                new CollectionResource($collection),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Patch(
     *     path="/collections/restore/{id}",
     *     tags={"Collections"},
     *     summary="Restaurar una colección eliminada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la colección", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Colección restaurada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="collection", ref="#/components/schemas/Collection")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="La colección no está eliminada"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function restore(CollectionRestoreRequest $request, $id)
    {
        try {
            $collection = Collection::withTrashed()->findOrFail($id);
            $collection->restore();

            return $this->successResponse(
                new CollectionResource($collection),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

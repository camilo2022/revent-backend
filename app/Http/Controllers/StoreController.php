<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\StoreAllRequest;
use App\Http\Requests\Store\StoreDeleteRequest;
use App\Http\Requests\Store\StoreFindRequest;
use App\Http\Requests\Store\StoreRestoreRequest;
use App\Http\Requests\Store\StoreStoreRequest;
use App\Http\Requests\Store\StoreUpdateRequest;
use App\Http\Resources\Store\StoreCollection;
use App\Http\Resources\Store\StoreResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\Store;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @OA\Tag(
 *     name="Stores",
 *     description="Endpoints para gestionar tiendas"
 * )
 *
 * @OA\Schema(
 *     schema="Store",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="00"),
 *     @OA\Property(property="name", type="string", example="Nombre legal"),
 *     @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
 *     @OA\Property(property="location_id", type="integer", example=1),
 *     @OA\Property(property="address", type="string", example="Calle 7"),
 *     @OA\Property(property="neighborhood", type="string", example="Barrio XYZ"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="location",
 *         discriminator=@OA\Discriminator(
 *             propertyName="type",
 *             mapping={
 *                 "country"="#/components/schemas/Country",
 *                 "department"="#/components/schemas/Department",
 *                 "city"="#/components/schemas/City"
 *             }
 *         ),
 *         oneOf={
 *             @OA\Schema(ref="#/components/schemas/Country"),
 *             @OA\Schema(ref="#/components/schemas/Department"),
 *             @OA\Schema(ref="#/components/schemas/City")
 *         }
 *     )
 * )
 */
class StoreController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/stores/all",
     *     tags={"Stores"},
     *     summary="Listar los tiendas",
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
     *         @OA\Schema(type="string", enum={"id","code","name","location_id","location_type","address","neighborhood","created_at","updated_at","deleted_at"}, example="name")
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
     *         description="Lista de tiendas cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "users",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Store")
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
    public function all(StoreAllRequest $request)
    {
        try {
            $stores = Store::with([
                    'location' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        Country::class => ['region' => ['continent']],
                        Department::class => ['country' => ['region' => ['continent']]],
                        City::class => ['department' => ['country' => ['region' => ['continent']]]],
                    ])
                ])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) use ($request) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $stores = $stores->paginate($request->integer('per_page', $stores->count()));

            return $this->successResponse(
                new StoreCollection($stores),
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
     *     path="/stores/find/{id}",
     *     tags={"Stores"},
     *     summary="Obtener una tienda específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la tienda",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tienda cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="store",
     *                     ref="#/components/schemas/Store"
     *                 ),
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
    public function find(StoreFindRequest $request, $id)
    {
        try {
            $store = Store::with([
                    'location' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        Country::class => ['region' => ['continent']],
                        Department::class => ['country' => ['region' => ['continent']]],
                        City::class => ['department' => ['country' => ['region' => ['continent']]]],
                    ])
                ])->findOrFail($id);

            return $this->successResponse(
                new StoreResource($store),
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
     * @OA\Post(
     *     path="/stores/store",
     *     tags={"Stores"},
     *     summary="Crear una tienda",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"id","code","name","location_id","location_type","address","neighborhood"},
     *                 @OA\Property(property="code", type="string", example="00"),
     *                 @OA\Property(property="name", type="string", example="Nombre legal"),
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                 @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *                 @OA\Property(property="neighborhood", type="string", example="Barrio XYZ")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tienda creada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="store",
     *                     type="object",
     *                     ref="#/components/schemas/Store"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                  @OA\Property(property="code", type="string", example="00"),
     *                  @OA\Property(property="name", type="string", example="Nombre legal"),
     *                  @OA\Property(property="trade_name", type="string", example="Nombre comercial"),
     *                  @OA\Property(property="location_id", type="string", example="Ubicación"),
     *                  @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                  @OA\Property(property="address", type="string", example="Dirección"),
     *                  @OA\Property(property="neighborhood", type="string", example="Barrio")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
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
    public function store(StoreStoreRequest $request)
    {
        try {
            $store = new Store();
            $store->code = $request->input('code');
            $store->name = $request->input('name');
            $store->location_id = $request->integer('location_id');
            $store->location_type = $request->input('location_type');
            $store->address = $request->input('address');
            $store->neighborhood = $request->input('neighborhood');
            $store->save();

            return $this->successResponse(
                new StoreResource($store),
                $this->getMessage('Success'),
                201
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
     * @OA\Put(
     *     path="/stores/update/{id}",
     *     tags={"Stores"},
     *     summary="Editar la información de una tienda en específico",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"id","code","name","location_id","location_type","address","neighborhood"},
     *                 @OA\Property(property="code", type="string", example="00"),
     *                 @OA\Property(property="name", type="string", example="Nombre legal"),
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                 @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *                 @OA\Property(property="neighborhood", type="string", example="Barrio XYZ")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tienda editada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="store",
     *                     type="object",
     *                     ref="#/components/schemas/Store"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                  property="attributes",
     *                  type="object",
     *                  @OA\Property(property="code", type="string", example="00"),
     *                  @OA\Property(property="name", type="string", example="Nombre legal"),
     *                  @OA\Property(property="location_id", type="string", example="Ubicación"),
     *                  @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                  @OA\Property(property="address", type="string", example="Dirección"),
     *                  @OA\Property(property="neighborhood", type="string", example="Barrio")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
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
    public function update(StoreUpdateRequest $request, $id)
    {
        try {
            $store = Store::findOrFail($id);
            $store->code = $request->input('code');
            $store->name = $request->input('name');
            $store->location_id = $request->integer('location_id');
            $store->location_type = $request->input('location_type');
            $store->address = $request->input('address');
            $store->neighborhood = $request->input('neighborhood');
            $store->save();

            return $this->successResponse(
                new StoreResource($store),
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
     * @OA\Delete(
     *     path="/stores/delete/{id}",
     *     tags={"Stores"},
     *     summary="Desactivar una tienda específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la tienda",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tienda desactivada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="store",
     *                     type="object",
     *                     ref="#/components/schemas/Store"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador de la tienda")
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
    public function delete(StoreDeleteRequest $request, $id)
    {
        try {
            $store = Store::findOrFail($id);
            $store->delete();

            return $this->successResponse(
                new StoreResource($store),
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
     * @OA\Patch(
     *     path="/stores/restore/{id}",
     *     tags={"Stores"},
     *     summary="Activar una tienda específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la tienda",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tienda activada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="store",
     *                     type="object",
     *                     ref="#/components/schemas/Store"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador de la tienda")
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
    public function restore(StoreRestoreRequest $request, $id)
    {
        try {
            $store = Store::withTrashed()->findOrFail($id);
            $store->restore();

            return $this->successResponse(
                new StoreResource($store),
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
}

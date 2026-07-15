<?php

namespace App\Http\Controllers;

use App\Http\Requests\Navegation\Submodule\SubmoduleAllRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleDeleteRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleFindRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleRestoreRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleStoreRequest;
use App\Http\Requests\Navegation\Submodule\SubmoduleUpdateRequest;
use App\Http\Resources\Navegation\Submodule\SubmoduleCollection;
use App\Http\Resources\Navegation\Submodule\SubmoduleResource;
use App\Models\Submodule;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Navegation - Submodules",
 *     description="Endpoints para gestionar de Submódulos"
 * )
 *
 * @OA\Schema(
 *     schema="Submodule",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="users"),
 *     @OA\Property(property="url", type="string", example="/users"),
 *     @OA\Property(property="icon", type="string", example="FaUser"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="module",
 *         type="object",
 *         ref="#/components/schemas/Module"
 *     ),
 *     @OA\Property(
 *         property="permission",
 *         type="object",
 *         ref="#/components/schemas/Permission"
 *     )
 * )
 */
class SubmoduleController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/navegation/modules/submodules/all/{module_id}",
     *     tags={"Navegation - Submodules"},
     *     summary="Listar los Submódulos",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="module_id",
     *         in="path",
     *         description="Identificador del módulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
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
     *         @OA\Schema(type="string", enum={"id","name","title", "description","created_at", "updated_at"}, example="name")
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
     *         description="Lista de submódulos cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="submodules",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Submodule")
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
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del módulo no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="module_id", type="string", example="Identificador del módulo. "),
     *                 @OA\Property(property="per_page", type="string", example="Registros por pagina. "),
     *                 @OA\Property(property="search", type="string", example="Filtro de busqueda. "),
     *                 @OA\Property(property="column", type="string", example="Columna a ordenar. "),
     *                 @OA\Property(property="dir", type="string", example="Sentido del orden. ")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="module_id",
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
    public function all(SubmoduleAllRequest $request, $module_id)
    {
        try {
            $submodules = Submodule::with(['module', 'permission'])
                ->whereHas('module', fn($r) => $r->where('id', $module_id))
                ->when($request->input('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->input('column') && $request->input('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $submodules = $submodules->paginate($request->input('per_page', $submodules->count()));

            return $this->successResponse(
                new SubmoduleCollection($submodules),
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
     *     path="/navegation/modules/submodules/find/{id}",
     *     tags={"Navegation - Submodules"},
     *     summary="Listar los Submódulos",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del submódulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo cargado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="submodule",
     *                     ref="#/components/schemas/Submodule"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del sumódulo no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del submódulo. ")
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
    public function find(SubmoduleFindRequest $request)
    {
        try {
            $submodule = Submodule::with(['module', 'permission'])->findOrFail($request->id);

            return $this->successResponse(
                new SubmoduleResource($submodule),
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
     *     path="/navegation/modules/submodules/store",
     *     tags={"Navegation - Submodules"},
     *     summary="Crear un submódulo",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Nombre del submódulo",
     *         required=true,
     *         @OA\Schema(type="string", example="name_submodule")
     *     ),
     *     @OA\Parameter(
     *         name="icon",
     *         in="query",
     *         description="Icono del submódulo",
     *         required=true,
     *         @OA\Schema(type="string", example="icon_submodule")
     *     ),
     *     @OA\Parameter(
     *         name="url",
     *         in="query",
     *         description="Url del submódulo",
     *         required=true,
     *         @OA\Schema(type="string", example="url_submodule")
     *     ),
     *     @OA\Parameter(
     *         name="module_id",
     *         in="query",
     *         description="Identificador del módulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="permission_id",
     *         in="query",
     *         description="Identificador del módulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"module_id","permission_id","name","url","icon"},
     *             @OA\Property(
     *                 property="module_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador del módulo"
     *             ),
     *             @OA\Property(
     *                 property="permission_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador del permiso"
     *             ),
     *              @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Usuarios",
     *                 description="Nombre del submódulo"
     *             ),
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 example="/users",
     *                 description="URL del submódulo"
     *              ),
     *             @OA\Property(
     *                 property="icon",
     *                 type="string",
     *                 example="FaUser",
     *                 description="Icono del submódulo"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="submodule",
     *                     ref="#/components/schemas/Submodule"
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
     *                 @OA\Property(property="module_id", type="string", example="Identificador del módulo. "),
     *                 @OA\Property(property="permission_id", type="string", example="Identificador del permiso. "),
     *                 @OA\Property(property="name", type="string", example="Nombre del submódulo"),
     *                 @OA\Property(property="url", type="string", example="Url del submódulo"),
     *                 @OA\Property(property="icon", type="string", example="Icono del submódulo")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="module_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya está registrado.")
     *                 ),
     *                 @OA\Property(
     *                     property="url",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya está registrado.")
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
    public function store(SubmoduleStoreRequest $request)
    {
        try {
            $submodule = new Submodule();

            $submodule->name = $request->input('name');
            $submodule->url = $request->input('url');
            $submodule->icon = $request->input('icon');
            $submodule->module_id = $request->integer('module_id');
            $submodule->permission_id = $request->integer('permission_id');

            $submodule->save();

            $submodule->load(['module', 'permission']);

            return $this->successResponse(
                [
                    'submodule' => $submodule
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
     * @OA\Put(
     *     path="/navegation/modules/submodules/update/{id}",
     *     tags={"Navegation - Submodules"},
     *     summary="Crear un submódulo",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del submódulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"module_id","permission_id","name","url","icon"},
     *             @OA\Property(
     *                 property="module_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador del módulo"
     *             ),
     *             @OA\Property(
     *                 property="permission_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador del permiso"
     *             ),
     *              @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Usuarios",
     *                 description="Nombre del submódulo"
     *             ),
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 example="/users",
     *                 description="URL del submódulo"
     *              ),
     *             @OA\Property(
     *                 property="icon",
     *                 type="string",
     *                 example="FaUser",
     *                 description="Icono del submódulo"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo actualizado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="submodule",
     *                     ref="#/components/schemas/Submodule"
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
     *                 @OA\Property(property="module_id", type="string", example="Identificador del módulo. "),
     *                 @OA\Property(property="permission_id", type="string", example="Identificador del permiso. "),
     *                 @OA\Property(property="name", type="string", example="Nombre del submódulo"),
     *                 @OA\Property(property="url", type="string", example="Url del submódulo"),
     *                 @OA\Property(property="icon", type="string", example="Icono del submódulo")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="module_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya está registrado.")
     *                 ),
     *                 @OA\Property(
     *                     property="url",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya está registrado.")
     *                 ),
     *                 @OA\Property(
     *                     property="permission_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya está registrado.")
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
    public function update(SubmoduleUpdateRequest $request, $id)
    {
        try {
            $submodule = Submodule::findOrFail($id);

            $submodule->name = $request->input('name');
            $submodule->url = $request->input('url');
            $submodule->icon = $request->input('icon');
            $submodule->module_id = $request->integer('module_id');
            $submodule->permission_id = $request->integer('permission_id');

            $submodule->save();

            $submodule->load(['module', 'permission']);

            return $this->successResponse(
                [
                    'submodule' => $submodule
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
     * @OA\Delete(
     *     path="/navegation/modules/submodules/delete/{id}",
     *     tags={"Navegation - Submodules"},
     *     summary="Desactivar un submódulo específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del submódulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo desactivado correctamente",
     *         @OA\JsonContent(
     *            @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="submodule",
     *                     ref="#/components/schemas/Submodule"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del submódulo no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del submódulo")
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
    public function delete(SubmoduleDeleteRequest $request)
    {
        try {
            $submodule = Submodule::findOrFail($request->id);
            $submodule->delete();

            $submodule->load(['module', 'permission']);

            return $this->successResponse(
                [
                    'submodule' => $submodule
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
     * @OA\Patch(
     *     path="/navegation/modules/submodules/restore/{id}",
     *     tags={"Navegation - Submodules"},
     *     summary="Activar un submódulo específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del submódulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submódulo activado correctamente",
     *         @OA\JsonContent(
     *            @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="submodule",
     *                     ref="#/components/schemas/Submodule"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del submódulo no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del submódulo")
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
    public function restore(SubmoduleRestoreRequest $request)
    {
        try {
            $submodule = Submodule::withTrashed()->findOrFail($request->id);
            $submodule->restore();

            $submodule->load(['module', 'permission']);

            return $this->successResponse(
                [
                    'submodule' => $submodule
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
}

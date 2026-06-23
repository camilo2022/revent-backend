<?php

namespace App\Http\Controllers;

use App\Http\Requests\Navegation\Module\ModuleAllRequest;
use App\Http\Requests\Navegation\Module\ModuleDeleteRequest;
use App\Http\Requests\Navegation\Module\ModuleFindRequest;
use App\Http\Requests\Navegation\Module\ModuleRestoreRequest;
use App\Http\Requests\Navegation\Module\ModuleStoreRequest;
use App\Http\Requests\Navegation\Module\ModuleUpdateRequest;
use App\Http\Resources\Navegation\Module\ModuleCollection;
use App\Http\Resources\Navegation\Module\ModuleResource;
use App\Models\Module;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Navegation - Modules",
 *     description="Endpoints para gestionar de Módulos"
 * )
 *
 * @OA\Schema(
 *     schema="Module",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="users"),
 *     @OA\Property(property="icon", type="string", example="FaUser"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="submodules",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="users"),
 *             @OA\Property(property="url", type="string", example="/users"),
 *             @OA\Property(property="icon", type="string", example="FaUser"),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *         )
 *     )
 * )
 */
class ModuleController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/navegation/modules/all",
     *     tags={"Navegation - Modules"},
     *     summary="Listar los módulos",
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
     *         description="Lista de módulos cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="modules",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Module")
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
    public function all(ModuleAllRequest $request)
    {
        try {
            $modules = Module::with(['submodules'])
                ->when($request->input('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) use ($request) {
                    return $query->withTrashed();
                })
                ->when($request->input('column') && $request->input('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $modules = $modules->paginate($request->input('per_page', $modules->count()));

            return $this->successResponse(
                new ModuleCollection($modules),
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
     *     path="/navegation/modules/find/{id}",
     *     tags={"Navegation - Modules"},
     *     summary="Obtener un módulo específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del módulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role",
     *                     ref="#/components/schemas/Module"
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
     *                 @OA\Property(property="id", type="string", example="Identificador del módulo")
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
    public function find(ModuleFindRequest $request, $id)
    {
        try {
            $module = Module::findOrFail($id);

            $module->load(['submodules']);

            return $this->successResponse(
                new ModuleResource($module),
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
     *     path="/navegation/modules/store",
     *     tags={"Navegation - Modules"},
     *     summary="Crear un módulo",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","icon"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Usuarios",
     *                 description="Nombre del módulo"
     *             ),
     *             @OA\Property(
     *                 property="icon",
     *                 type="string",
     *                 example="FaUser",
     *                 description="Icono del módulo"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="modules",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="name_module_example"),
     *                         @OA\Property(property="icon", type="string", example="icon_module_example"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="id", type="integer", example=0)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del módulo"),
     *                 @OA\Property(property="icon", type="string", example="Icono del módulo")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
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
    public function store(ModuleStoreRequest $request)
    {
        try {
            $module = new Module();

            $module->name = $request->input('name');
            $module->icon = $request->input('icon');

            $module->save();

            return $this->successResponse(
                new ModuleResource($module),
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
     *     path="/navegation/modules/update/{id}",
     *     tags={"Navegation - Modules"},
     *     summary="Editar un módulo específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del módulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","icon"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Usuarios",
     *                 description="Nombre del módulo"
     *             ),
     *             @OA\Property(
     *                 property="icon",
     *                 type="string",
     *                 example="FaUser",
     *                 description="Icono del módulo"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo actualizado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="modules",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=0),
     *                         @OA\Property(property="name", type="string", example="name_module_example"),
     *                         @OA\Property(property="icon", type="string", example="icon_module_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="delete_at", type="string", format="date-time", nullable=true, example=null)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="Nombre del módulo"),
     *                 @OA\Property(property="icon", type="string", example="Icono del módulo")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
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
    public function update(ModuleUpdateRequest $request, $id)
    {
        try {
            $module = Module::findOrFail($id);

            $module->name = $request->input('name');
            $module->icon =  $request->input('icon');

            $module->save();

            return $this->successResponse(
                new ModuleResource($module),
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
     *     path="/navegation/modules/delete/{id}",
     *     tags={"Navegation - Modules"},
     *     summary="Desactivar un módulo específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del módulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo desactivado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="modules",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=0),
     *                         @OA\Property(property="name", type="string", example="name_module_example"),
     *                         @OA\Property(property="icon", type="string", example="icon_module_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="delete_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                     )
     *                 )
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
     *                 @OA\Property(property="id", type="string", example="Identificador del módulo")
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
    public function delete(ModuleDeleteRequest $request)
    {
        try {
            $module = Module::findOrFail($request->id);
            $module->delete();

            return $this->successResponse(
                new ModuleResource($module),
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
     *     path="/navegation/modules/restore/{id}",
     *     tags={"Navegation - Modules"},
     *     summary="Activar un módulo específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del módulo",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Módulo restaurado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="modules",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=0),
     *                         @OA\Property(property="name", type="string", example="name_module_example"),
     *                         @OA\Property(property="icon", type="string", example="icon_module_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="delete_at", type="string", format="date-time", nullable=true, example=null)
     *                     )
     *                 )
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
     *                 @OA\Property(property="id", type="string", example="Identificador del módulo")
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
    public function restore(ModuleRestoreRequest $request)
    {
        try {
            $module = Module::withTrashed()->findOrFail($request->id);
            $module->restore();

            return $this->successResponse(
                new ModuleResource($module),
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

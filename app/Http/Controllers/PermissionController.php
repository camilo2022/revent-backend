<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authorization\Permission\PermissionAllRequest;
use App\Http\Requests\Authorization\Permission\PermissionFindRequest;
use App\Http\Requests\Authorization\Permission\PermissionStoreRequest;
use App\Http\Requests\Authorization\Permission\PermissionUpdateRequest;
use App\Http\Resources\Authorization\Permission\PermissionCollection;
use App\Http\Resources\Authorization\Permission\PermissionResource;
use App\Models\Permission;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Authorization - Permissions",
 *     description="Endpoints para gestionar de Permisos"
 * )
 *
 * @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="users"),
 *     @OA\Property(property="title", type="string", example="Usuarios."),
 *     @OA\Property(property="description", type="string", example="Gestión de usuarios."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(
 *         property="role",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="users"),
 *         @OA\Property(property="guard_name", type="string", example="api"),
 *         @OA\Property(property="title", type="string", example="Usuarios."),
 *         @OA\Property(property="description", type="string", example="Gestión de usuarios."),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *         @OA\Property(
 *             property="pivot",
 *             type="object",
 *             @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
 *             @OA\Property(property="model_id", type="integer", example=1),
 *             @OA\Property(property="role_id", type="integer", example=1)
 *         )
 *     )
 * )
 */
class PermissionController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/authorization/permissions/all",
     *     tags= {"Authorization - Permissions"},
     *     summary="Listar los permisos",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="Identificador del Rol.",
     *         required=false,
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de permisos cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="permissions",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Permission")
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
    public function all(PermissionAllRequest $request)
    {
        try {
            $permissions = Permission::with(['roles'])
                ->when($request->filled('role_id'), fn($query) => $query->whereHas('roles', fn($q) => $q->where('id', $request->input('role_id'))))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $permissions = $permissions->paginate($request->integer('per_page', $permissions->count()));

            return $this->successResponse(
                new PermissionCollection($permissions),
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
     *     path="/authorization/permissions/find/{id}",
     *     tags= {"Authorization - Permissions"},
     *     summary="Obtener un permiso específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del permiso",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                   property="permission",
     *                   ref="#/components/schemas/Permission"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de permiso no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del permiso")
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
    public function find(PermissionFindRequest $request, $id)
    {
        try {
            $permission = permission::with(['roles'])
                ->findOrFail($id);

            return $this->successResponse(
                new PermissionResource($permission),
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
     *     path="/authorization/permissions/store",
     *     tags={"Authorization - Permissions"},
     *     summary="Crear un permiso",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role_id","name","tittle","description"},
     *             @OA\Property(
     *                 property="role_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador del Rol"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="user.all",
     *                 description="Nombre del permiso"
     *             ),
     *             @OA\Property(
     *                 property="tittle",
     *                 type="string",
     *                 example="Listar Usuarios",
     *                 description="Título del permiso"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  example="Permite listar los usuarios",
     *                  description="Descripción del permiso"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="permission",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="name_permission_example"),
     *                     @OA\Property(property="title", type="string", example="title_permission_example"),
     *                     @OA\Property(property="description", type="string", example="description_permission_example"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="id", type="integer", example=1)
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="role_id", type="integer", example="Identificador del rol"),
     *                 @OA\Property(property="name", type="string", example="Nombre del permiso"),
     *                 @OA\Property(property="title", type="string", example="Título del permiso"),
     *                 @OA\Property(property="description", type="string", example="Descripción del permiso")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role_id",
     *                     type="array",
     *                     @OA\Items(type="numeric", example="El identificador del rol no existe.")
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
     *                 ),
     *                 @OA\Property(
     *                     property="title",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
     *                 ),
     *                 @OA\Property(
     *                     property="description",
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
    public function store(PermissionStoreRequest $request)
    {
        try {
            $permission = new Permission();
            $permission->name = $request->input('name');
            $permission->title =  $request->input('title');
            $permission->description = $request->input('description');
            $permission->save();

            $permission->syncRoles($request->integer('role_id'));

            return $this->successResponse(
                new PermissionResource($permission),
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
     *     path="/authorization/permissions/update/{id}",
     *     tags={"Authorization - Permissions"},
     *     summary="Editar un permiso específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del permiso",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role_id","name","tittle","description"},
     *             @OA\Property(
     *                 property="role_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador del Rol"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="user.all",
     *                 description="Nombre del permiso"
     *             ),
     *             @OA\Property(
     *                 property="tittle",
     *                 type="string",
     *                 example="Listar Usuarios",
     *                 description="Título del permiso"
     *              ),
     *              @OA\Property(
     *                  property="description",
     *                  type="string",
     *                  example="Permite listar los usuarios",
     *                  description="Descripción del permiso"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso editado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="permission",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="name_permission_example"),
     *                     @OA\Property(property="title", type="string", example="title_permission_example"),
     *                     @OA\Property(property="description", type="string", example="description_permission_example"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="role_id", type="integer", example="role_id_example"),
     *                 @OA\Property(property="id", type="string", example="Identificador del permiso"),
     *                 @OA\Property(property="name", type="string", example="Nombre del permiso"),
     *                 @OA\Property(property="title", type="string", example="Título del permiso"),
     *                 @OA\Property(property="description", type="string", example="Descripción del permiso")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="No hay ningún registro.")
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
     *                 ),
     *                 @OA\Property(
     *                     property="title",
     *                     type="array",
     *                     @OA\Items(type="string", example="Es obligatorio.")
     *                 ),
     *                 @OA\Property(
     *                     property="description",
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
    public function update(PermissionUpdateRequest $request, $id)
    {
        try {
            $permission = Permission::findOrFail($id);
            $permission->name = $request->input('name');
            $permission->title =  $request->input('title');
            $permission->description = $request->input('description');
            $permission->save();

            $permission->syncRoles($request->integer('role_id'));

            return $this->successResponse(
                new PermissionResource($permission),
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

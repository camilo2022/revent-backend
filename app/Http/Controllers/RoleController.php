<?php

namespace App\Http\Controllers;

use App\Http\Requests\Authorization\Role\RoleAllRequest;
use App\Http\Requests\Authorization\Role\RoleFindRequest;
use App\Http\Requests\Authorization\Role\RoleStoreRequest;
use App\Http\Requests\Authorization\Role\RoleUpdateRequest;
use App\Http\Resources\Authorization\Role\RoleCollection;
use App\Http\Resources\Authorization\Role\RoleResource;
use App\Models\Role;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Authorization - Roles",
 *     description="Endpoints para gestionar de Roles"
 * )
 *
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="users"),
 *     @OA\Property(property="guard_name", type="string", example="api"),
 *     @OA\Property(property="title", type="string", example="Usuarios."),
 *     @OA\Property(property="description", type="string", example="Gestión de usuarios."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Permission")
 *     )
 * )
 */
class RoleController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/authorization/roles/all",
     *     tags={"Authorization - Roles"},
     *     summary="Listar los roles",
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
     *         @OA\Schema(type="string", enum={"id","name","title", "description","created_at", "updated_at"}, example="id")
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
     *         description="Lista de roles cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="roles",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Items(ref="#/components/schemas/Role")
     *                     )
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
    public function all(RoleAllRequest $request)
    {
        try {
            $roles = Role::with(['permissions'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $roles = $roles->paginate($request->integer('per_page', $roles->count()));

            return $this->successResponse(
                new RoleCollection($roles),
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
     *     path="/authorization/roles/find/{id}",
     *     tags= {"Authorization - Roles"},
     *     summary="Obtener un rol específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del rol",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role",
     *                     ref="#/components/schemas/Role"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de rol no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del rol")
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
    public function find(RoleFindRequest $request, $id)
    {
        try {
            $role = Role::with(['permissions'])
                ->findOrFail($id);

            return $this->successResponse(
                new RoleResource($role),
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
     *     path="/authorization/roles/store",
     *     tags= {"Authorization - Roles"},
     *     summary="Crear un rol",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","tittle","description"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Usuarios",
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
     *         description="Rol creado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="name_rol_example"),
     *                     @OA\Property(property="title", type="string", example="title_rol_example"),
     *                     @OA\Property(property="description", type="string", example="description_rol_example"),
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
     *                 @OA\Property(property="name", type="string", example="Nombre del rol"),
     *                 @OA\Property(property="title", type="string", example="Título del rol"),
     *                 @OA\Property(property="description", type="string", example="Descripción del rol")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
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
    public function store(RoleStoreRequest $request)
    {
        try {
            $role = new Role();
            $role->name = $request->input('name');
            $role->title =  $request->input('title');
            $role->description = $request->input('description');
            $role->save();

            return $this->successResponse(
                new RoleResource($role),
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
     *     path="/authorization/roles/update/{id}",
     *     tags= {"Authorization - Roles"},
     *     summary="Editar un rol específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del rol",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","tittle","description"},
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
     *         description="Rol editado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="role",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="id_example"),
     *                     @OA\Property(property="name", type="string", example="name_rol_example"),
     *                     @OA\Property(property="title", type="string", example="title_rol_example"),
     *                     @OA\Property(property="description", type="string", example="description_rol_example"),
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
     *                 @OA\Property(property="id", type="string", example="Identificador del rol"),
     *                 @OA\Property(property="name", type="string", example="Nombre del rol"),
     *                 @OA\Property(property="title", type="string", example="Título del rol"),
     *                 @OA\Property(property="description", type="string", example="Descripción del rol")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
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
    public function update(RoleUpdateRequest $request, $id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->name = $request->input('name');
            $role->title =  $request->input('title');
            $role->description = $request->input('description');
            $role->save();

            return $this->successResponse(
                new RoleResource($role),
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

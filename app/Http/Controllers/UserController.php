<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UserAllRequest;
use App\Http\Requests\User\UserAuthorizationAssignRequest;
use App\Http\Requests\User\UserDeleteRequest;
use App\Http\Requests\User\UserFindRequest;
use App\Http\Requests\User\UserAuthorizationRemoveRequest;
use App\Http\Requests\User\UserRestoreRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\Permission;
use App\Models\User;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Endpoints para gestionar usuarios"
 * )
 *
 * @OA\PathItem(
 *     path="/users",
 *     description="Rutas de gestión de usuarios"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="email", type="string", example="email_user_example"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(
 *         property="employee",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="employee_id", type="integer", example=1),
 *         @OA\Property(property="person_id", type="integer", example=1),
 *         @OA\Property(property="operation_center", type="string", example="Principal"),
 *         @OA\Property(property="position_id", type="integer", example=2),
 *         @OA\Property(property="risk_manager_id", type="integer", example=5),
 *         @OA\Property(property="health_entity_id", type="integer", example=6),
 *         @OA\Property(property="pension_fund_id", type="integer", example=7),
 *         @OA\Property(property="compensation_fund_id", type="integer", example=8),
 *         @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30 00:00:00"),
 *         @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *         @OA\Property(
 *             property="position",
 *             ref="#/components/schemas/Position"
 *         ),
 *         @OA\Property(
 *             property="person",
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="document", type="string", example="0000000000"),
 *             @OA\Property(property="names", type="string", example="Super"),
 *             @OA\Property(property="last_names", type="string", example="Admin"),
 *             @OA\Property(property="gender_id", type="integer", example=15),
 *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
 *             @OA\Property(property="blood_type_id", type="integer", example=16),
 *             @OA\Property(property="address", type="string", example="Dirección principal"),
 *             @OA\Property(property="phone", type="string", example="0000000000"),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
 *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 *         )
 *     ),
 *     @OA\Property(
 *         property="roles",
 *         type="array",
 *         @OA\Items(
 *             allOf={
 *                  @OA\Schema(ref="#/components/schemas/Role"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="pivot",
 *                          type="object",
 *                          @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
 *                          @OA\Property(property="model_id", type="integer", example=1),
 *                          @OA\Property(property="role_id", type="integer", example=1)
 *                      )
 *                  )
 *             }
 *         )
 *     ),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         @OA\Items(
 *             allOf={
 *                  @OA\Schema(ref="#/components/schemas/Permission"),
 *                  @OA\Schema(
 *                      @OA\Property(
 *                          property="pivot",
 *                          type="object",
 *                          @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
 *                          @OA\Property(property="model_id", type="integer", example=1),
 *                          @OA\Property(property="role_id", type="integer", example=1)
 *                      )
 *                  )
 *             }
 *         )
 *     )
 * )
 */


class UserController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/users/all",
     *     tags={"Users"},
     *     summary="Listar los usuarios",
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
     *         @OA\Schema(type="string", enum={"id","email","created_at", "updated_at"}, example="email")
     *     ),
     *     @OA\Parameter(
     *         name="dir",
     *         in="query",
     *         description="Dirección de ordenamiento.",
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
     *         description="Lista de usuarios cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "users",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/User")
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
    public function all(UserAllRequest $request)
    {
        try {
            $users = User::with(['employee' => ['person', 'position' => ['area']], 'roles', 'permissions'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) use ($request) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $users = $users->paginate($request->integer('per_page'), ['*'], 'page', $request->integer('page', 1));

            return $this->successResponse(
                new UserCollection($users),
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
     *     path="/users/find/{id}",
     *     tags={"Users"},
     *     summary="Obtener un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                   property="user",
     *                   ref="#/components/schemas/User"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de usuario no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario")
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
    public function find(UserFindRequest $request, $id)
    {
        try {
            $user = User::with(['employee' => ['person', 'position' => ['area']], 'roles', 'permissions'])
                ->findOrFail($id);

            return $this->successResponse(
                new UserResource($user),
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
     *     path="/users/store",
     *     tags={"Users"},
     *     summary="Crear un usuario",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id","email","password","password_confirmation"},
     *             @OA\Property(
     *                 property="employee_id",
     *                 type="string",
     *                 example="1",
     *                 description="Empleado"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="user@example.com",
     *                 description="Correo electrónico del usuario"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="Contrasena12!",
     *                 description="Contraseña del usuario"
     *              ),
     *              @OA\Property(
     *                  property="password_confirmation",
     *                  type="string",
     *                  format="password",
     *                  example="Contrasena12!",
     *                  description="Confirmación contraseña del usuario"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="object",
     *                     @OA\Property(property="employee_id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="email_user_example"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="employee",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="person_id", type="integer", example=1),
     *                         @OA\Property(property="operation_center", type="string", example="Principal"),
     *                         @OA\Property(property="position_id", type="integer", example=2),
     *                         @OA\Property(property="risk_manager_id", type="integer", example=5),
     *                         @OA\Property(property="health_entity_id", type="integer", example=6),
     *                         @OA\Property(property="pension_fund_id", type="integer", example=7),
     *                         @OA\Property(property="compensation_fund_id", type="integer", example=8),
     *                         @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30 00:00:00"),
     *                         @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *                         @OA\Property(
     *                             property="position",
     *                             ref="#/components/schemas/PositionWithRelations"
     *                         ),
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
     *                 @OA\Property(property="employee_id", type="string", example="Empleado"),
     *                 @OA\Property(property="email", type="string", example="Correo electrónico del usuario"),
     *                 @OA\Property(property="password", type="string", example="Contraseña del usuario"),
     *                 @OA\Property(property="password_confirmation", type="string", example="Confirmación contraseña del usuario")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
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
    public function store(UserStoreRequest $request)
    {
        try {
            $user = new User();
            $user->employee_id = $request->input('employee_id');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            $user->save();

            $user->load(['employee' => ['position' => ['area', 'roles', 'permissions']]]);
            $user->syncRoles($user->employee->position->roles);
            $user->syncPermissions($user->employee->position->permissions);

            return $this->successResponse(
                new UserResource($user),
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
     *     path="/users/update/{id}",
     *     tags={"Users"},
     *     summary="Editar un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id","email","password","password_confirmation"},
     *             @OA\Property(
     *                 property="employee_id",
     *                 type="string",
     *                 example="1",
     *                 description="Empleado"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="user@example.com",
     *                 description="Correo electrónico del usuario"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="Contrasena12!",
     *                 description="Contraseña del usuario"
     *              ),
     *              @OA\Property(
     *                  property="password_confirmation",
     *                  type="string",
     *                  format="password",
     *                  example="Contrasena12!",
     *                  description="Confirmación contraseña del usuario"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del usuario actualizada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="employee_id", type="integer", example=4),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *      @OA\Response(
     *         response=422,
     *         description="Contenido inválido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="employee_id", type="string", example="Empleado"),
     *                 @OA\Property(property="email", type="string", example="Correo electrónico del usuario"),
     *                 @OA\Property(property="password", type="string", example="Contraseña del usuario"),
     *                 @OA\Property(property="password_confirmation", type="string", example="Confirmación contraseña del usuario")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
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
    public function update(UserUpdateRequest $request, $id)
    {
        try {
            $user = User::findorFail($id);
            $user->employee_id = $request->integer('employee_id');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            $user->save();

            return $this->successResponse(
                new UserResource($user),
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
     *     path="/users/delete/{id}",
     *     tags={"Users"},
     *     summary="Desactivar un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=14),
     *                         @OA\Property(property="employee_id", type="integer", example=1),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", example="2026-03-12 20:01:03")
     *                     ),
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de usuario no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario")
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
    public function delete(UserDeleteRequest $request, $id)
    {
        try {
            $user = User::with(['roles', 'permissions'])
                ->findOrFail($id);
            $user->syncRoles([]);
            $user->permissions->each(function ($permission) use ($user) {
                $user->revokePermissionTo($permission);
            });
            $user->delete();

            return $this->successResponse(
                new UserResource($user),
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
     *     path="/users/restore/{id}",
     *     tags={"Users"},
     *     summary="Activar un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *    @OA\Response(
     *         response=200,
     *         description="Usuario creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="employee_id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="email_user_example"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="employee",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="person_id", type="integer", example=1),
     *                         @OA\Property(property="operation_center", type="string", example="Principal"),
     *                         @OA\Property(property="position_id", type="integer", example=2),
     *                         @OA\Property(property="risk_manager_id", type="integer", example=5),
     *                         @OA\Property(property="health_entity_id", type="integer", example=6),
     *                         @OA\Property(property="pension_fund_id", type="integer", example=7),
     *                         @OA\Property(property="compensation_fund_id", type="integer", example=8),
     *                         @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30 00:00:00"),
     *                         @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *                         @OA\Property(
     *                             property="position",
     *                             ref="#/components/schemas/PositionWithRelations"
     *                         ),
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador de usuario no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario")
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
    public function restore(UserRestoreRequest $request, $id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();


            $user->load(['employee' => ['position' => ['area', 'roles', 'permissions']]]);
            $user->syncRoles($user->employee->position->roles);
            $user->syncPermissions($user->employee->position->permissions);

            return $this->successResponse(
                new UserResource($user),
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
     *     path="/users/authorization/assign/{id}",
     *     tags={"Users"},
     *     summary="Asignar un permiso a un usuario específio",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permission_id"},
     *             @OA\Property(
     *                 property="permission_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador del Permiso"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso asignado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                   property="user",
     *                   ref="#/components/schemas/User"
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
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario"),
     *                 @OA\Property(property="permission_id", type="string", example="Identificador del permiso"),
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example= "No hay ningún registro.")
     *                 ),
     *                 @OA\Property(
     *                     property="permission_id",
     *                     type="array",
     *                     @OA\Items(type="string", example= "No hay ningún registro.")
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
    public function assign(UserAuthorizationAssignRequest $request, $id)
    {
        try {
            $permission = Permission::findOrFail($request->integer('permission_id'));
            $user = User::findOrFail($id);

            $user->givePermissionTo($permission);

            if (!$user->hasRole($permission->roles)) {
                $user->assignRole($permission->roles);
            }

            $user->load(['employee' => ['person', 'position' => ['area']], 'roles', 'permissions']);

            return $this->successResponse(
                new UserResource($user),
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
     *     path="/users/authorization/remove/{id}",
     *     tags={"Users"},
     *     summary="Remover un permiso a un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Identificador del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permission_id"},
     *             @OA\Property(
     *                 property="permission_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador del Permiso"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso removido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="email_user_example"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="roles",
     *                     type="array",
     *                     @OA\Items(
     *                         allOf={
     *                              @OA\Schema(ref="#/components/schemas/Role"),
     *                              @OA\Schema(
     *                                  @OA\Property(
     *                                      property="pivot",
     *                                      type="object",
     *                                      @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
     *                                      @OA\Property(property="model_id", type="integer", example=1),
     *                                      @OA\Property(property="role_id", type="integer", example=1)
     *                                  )
     *                              )
     *                         }
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="permissions",
     *                     type="array",
     *                     @OA\Items(
     *                         allOf={
     *                              @OA\Schema(ref="#/components/schemas/Permission"),
     *                              @OA\Schema(
     *                                  @OA\Property(
     *                                      property="pivot",
     *                                      type="object",
     *                                      @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
     *                                      @OA\Property(property="model_id", type="integer", example=1),
     *                                      @OA\Property(property="role_id", type="integer", example=1)
     *                                  )
     *                              )
     *                         }
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="employee",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="employee_id", type="integer", example=1),
     *                     @OA\Property(property="person_id", type="integer", example=1),
     *                     @OA\Property(property="operation_center", type="string", example="Principal"),
     *                     @OA\Property(property="position_id", type="integer", example=2),
     *                     @OA\Property(property="risk_manager_id", type="integer", example=5),
     *                     @OA\Property(property="health_entity_id", type="integer", example=6),
     *                     @OA\Property(property="pension_fund_id", type="integer", example=7),
     *                     @OA\Property(property="compensation_fund_id", type="integer", example=8),
     *                     @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30 00:00:00"),
     *                     @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(
     *                         property="position",
     *                         ref="#/components/schemas/Position"
     *                     ),
     *                     @OA\Property(
     *                         property="person",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="document", type="string", example="0000000000"),
     *                         @OA\Property(property="names", type="string", example="Super"),
     *                         @OA\Property(property="last_names", type="string", example="Admin"),
     *                         @OA\Property(property="gender_id", type="integer", example=15),
     *                         @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *                         @OA\Property(property="blood_type_id", type="integer", example=16),
     *                         @OA\Property(property="address", type="string", example="Dirección principal"),
     *                         @OA\Property(property="phone", type="string", example="0000000000"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                     )
     *                 ),
     *
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
     *                 @OA\Property(property="id", type="string", example="Identificador del usuario"),
     *                 @OA\Property(property="permission_id", type="string", example="Identificador del permiso"),
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="array",
     *                     @OA\Items(type="string", example= "No hay ningún registro.")
     *                 ),
     *                 @OA\Property(
     *                     property="permission_id",
     *                     type="array",
     *                     @OA\Items(type="string", example= "No hay ningún registro.")
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
    public function remove(UserAuthorizationRemoveRequest $request, $id)
    {
        try {
            $permission = Permission::findOrFail($request->integer('permission_id'));
            $user = User::findOrFail($id);

            $user->revokePermissionTo($permission);

            $user->roles->each(function ($role) use ($user) {
                if ($user->permissions->pluck('name')->intersect($role->permissions->pluck('name'))->isEmpty()) {
                    $user->removeRole($role);
                }
            });

            $user->load(['employee' => ['person', 'position' => ['area']], 'roles', 'permissions']);

            return $this->successResponse(
                new UserResource($user),
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

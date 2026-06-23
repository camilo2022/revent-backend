<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Module;
use App\Traits\ApiResponser;
use App\Traits\ApiMessage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Endpoints de autenticación"
 * )
 */
class AuthController extends Controller
{
    use ApiResponser, ApiMessage;

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Auth"},
     *     summary="Iniciar sesión de un usuario",
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Email del usuario",
     *         required=true,
     *         @OA\Schema(type="string", format="email", example="superadmin@kanri.com")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Contraseña del usuario",
     *         required=true,
     *         @OA\Schema(type="string", format="password", example="P4ssw0rd.")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Autenticación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "user",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="employee_id", type="integer", example=1),
     *                         @OA\Property(property="email", type="string", example="email_user_example"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", example=null),
     *                         @OA\Property(
     *                             property="employee",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="person_id", type="integer", example=1),
     *                             @OA\Property(property="operation_center", type="string", example="Principal"),
     *                             @OA\Property(property="position_id", type="integer", example=2),
     *                             @OA\Property(property="risk_manager_id", type="integer", example=5),
     *                             @OA\Property(property="health_entity_id", type="integer", example=6),
     *                             @OA\Property(property="pension_fund_id", type="integer", example=7),
     *                             @OA\Property(property="compensation_fund_id", type="integer", example=8),
     *                             @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30 00:00:00"),
     *                             @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *                             @OA\Property(
     *                                 property="person",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="document", type="string", example="0000000000"),
     *                                 @OA\Property(property="names", type="string", example="Super"),
     *                                 @OA\Property(property="last_names", type="string", example="Admin"),
     *                                 @OA\Property(property="gender_id", type="integer", example=15),
     *                                 @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *                                 @OA\Property(property="blood_type_id", type="integer", example=16),
     *                                 @OA\Property(property="address", type="string", example="Dirección principal"),
     *                                 @OA\Property(property="phone", type="string", example="0000000000"),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
     *                                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property="roles",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="users"),
     *                                 @OA\Property(property="guard_name", type="string", example="api"),
     *                                 @OA\Property(property="title", type="string", example="Usuarios."),
     *                                 @OA\Property(property="description", type="string", example="Gestión de usuarios."),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
     *                                 @OA\Property(
     *                                     property="pivot",
     *                                     type="object",
     *                                     @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
     *                                     @OA\Property(property="model_id", type="integer", example=1),
     *                                     @OA\Property(property="subitem_id", type="integer", example=1)
     *                                 )
     *                             ),
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=2),
     *                                 @OA\Property(property="name", type="string", example="users"),
     *                                 @OA\Property(property="guard_name", type="string", example="api"),
     *                                 @OA\Property(property="title", type="string", example="Usuarios."),
     *                                 @OA\Property(property="description", type="string", example="Gestión de usuarios."),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
     *                                 @OA\Property(
     *                                     property="pivot",
     *                                     type="object",
     *                                     @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
     *                                     @OA\Property(property="model_id", type="integer", example=1),
     *                                     @OA\Property(property="role_id", type="integer", example=1)
     *                                 )
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property="permissions",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="users.all"),
     *                                 @OA\Property(property="guard_name", type="string", example="api"),
     *                                 @OA\Property(property="title", type="string", example="Usuarios."),
     *                                 @OA\Property(property="description", type="string", example="Gestión de usuarios."),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
     *                                 @OA\Property(
     *                                     property="pivot",
     *                                     type="object",
     *                                     @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
     *                                     @OA\Property(property="model_id", type="integer", example=1),
     *                                     @OA\Property(property="role_id", type="integer", example=1)
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|tokenexampletokenexampletokenexample")
     *             ),
     *             @OA\Property(property="message", type="string", example="Autenticación exitosa"),
     *             @OA\Property(property="error", type="boolean", example="false")
     *         ),
     *      ),
     *      @OA\Response(
     *         response=404,
     *         description="Correo electrónico no registrado en la base de datos",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="No se encontraron resultados para la búsqueda."),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Contraseña inválida",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Contraseña inválida. Asegúrate de escribirla correctamente."),
     *                 @OA\Property(property="error", type="string", example="Credenciales incorrectas."),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", example={}),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $user = User::with(['employee' => ['person']])->where('email', $request->input('email'))->firstOrFail();

            if (!Hash::check($request->input('password'), $user->password)) {
                return $this->errorResponse(
                    [
                        'message' => $this->getMessage('UnauthorizedException'),
                        'error' => 'Credenciales incorrectas.'
                    ],
                    422
                );
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->load(['roles', 'permissions']);

            return $this->successResponse(
                [
                    'user' => $user,
                    'token' => $token
                ],
                'Autenticación exitosa',
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
     *     path="/auth/user",
     *     tags={"Auth"},
     *     summary="Obtener el usuario con sesión activa",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Usuario obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="employee_id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="email_user_example"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(
     *                         property="employee",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="employee_id", type="integer", example=1),
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
     *                             property="person",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="document", type="string", example="0000000000"),
     *                             @OA\Property(property="names", type="string", example="Super"),
     *                             @OA\Property(property="last_names", type="string", example="Admin"),
     *                             @OA\Property(property="gender_id", type="integer", example=15),
     *                             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *                             @OA\Property(property="blood_type_id", type="integer", example=16),
     *                             @OA\Property(property="address", type="string", example="Dirección principal"),
     *                             @OA\Property(property="phone", type="string", example="0000000000"),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
     *                             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *                             @OA\Property(
     *                                 property="gender",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="item_id", type="integer", example=4),
     *                                 @OA\Property(property="name", type="string", example="Super"),
     *                                 @OA\Property(property="description", type="string", example="Admin"),
     *                                 @OA\Property(property="settings", type="object", nullable=true),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
     *                                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                             ),
     *                             @OA\Property(
     *                                 property="blood_type",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="item_id", type="integer", example=4),
     *                                 @OA\Property(property="name", type="string", example="Super"),
     *                                 @OA\Property(property="description", type="string", example="Admin"),
     *                                 @OA\Property(property="settings", type="object", nullable=true),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
     *                                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property="position",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="item_id", type="integer", example=4),
     *                             @OA\Property(property="name", type="string", example="Super"),
     *                             @OA\Property(property="description", type="string", example="Admin"),
     *                             @OA\Property(property="settings", type="object", nullable=true),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
     *                             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *                             @OA\Property(
     *                                 property="area",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="id", type="integer", example=1),
     *                                     @OA\Property(property="item_id", type="integer", example=4),
     *                                     @OA\Property(property="name", type="string", example="Super"),
     *                                     @OA\Property(property="description", type="string", example="Admin"),
     *                                     @OA\Property(property="settings", type="object", nullable=true),
     *                                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
     *                                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *                                     @OA\Property(
     *                                         property="pivot",
     *                                         type="object",
     *                                         @OA\Property(property="model_type", type="string", example="App\\Models\\Position"),
     *                                         @OA\Property(property="model_id", type="integer", example=1),
     *                                         @OA\Property(property="subitem_id", type="integer", example=1)
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="roles",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="users"),
     *                             @OA\Property(property="guard_name", type="string", example="api"),
     *                             @OA\Property(property="title", type="string", example="Usuarios."),
     *                             @OA\Property(property="description", type="string", example="Gestión de usuarios."),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
     *                             @OA\Property(
     *                                 property="pivot",
     *                                 type="object",
     *                                 @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
     *                                 @OA\Property(property="model_id", type="integer", example=1),
     *                                 @OA\Property(property="role_id", type="integer", example=1)
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="permissions",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="users"),
     *                             @OA\Property(property="guard_name", type="string", example="api"),
     *                             @OA\Property(property="title", type="string", example="Usuarios."),
     *                             @OA\Property(property="description", type="string", example="Gestión de usuarios."),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
     *                             @OA\Property(
     *                                 property="pivot",
     *                                 type="object",
     *                                 @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
     *                                 @OA\Property(property="model_id", type="integer", example=1),
     *                                 @OA\Property(property="permission_id", type="integer", example=1)
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="navegation",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="users"),
     *                         @OA\Property(property="guard_name", type="string", example="api"),
     *                         @OA\Property(property="title", type="string", example="Usuarios."),
     *                         @OA\Property(property="description", type="string", example="Gestión de usuarios."),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
     *                         @OA\Property(
     *                             property="submodules",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="users"),
     *                                 @OA\Property(property="guard_name", type="string", example="api"),
     *                                 @OA\Property(property="title", type="string", example="Usuarios."),
     *                                 @OA\Property(property="description", type="string", example="Gestión de usuarios."),
     *                                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z")
     *                             )
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Usuario obtenido."),
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
     *             @OA\Property(property="message", type="string", example="No autenticado."),
     *             @OA\Property(property="error", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function user()
    {
        try {
            $user = Auth::user();

            $user->load(['employee' => ['person' => ['gender', 'blood_type'], 'position' => ['area'], 'risk_manager', 'health_entity', 'pension_fund', 'compensation_fund'], 'roles', 'permissions']);

            $navegation = Module::with(['submodules' => fn($query) => $query->whereIn('permission_id', Auth::user()->permissions->modelKeys())])
                ->whereHas('submodules', fn($query) => $query->whereIn('permission_id', Auth::user()->permissions->modelKeys()))
                ->get();

            return $this->successResponse(
                [
                    'user' => $user,
                    'navegation' => $navegation
                ],
                'Usuario obtenido',
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
     *     path="/auth/logout",
     *     tags={"Auth"},
     *     summary="Cerrar sesión",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cierre de sesión exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example="id_example"),
     *                     @OA\Property(property="name", type="string", example="name_example"),
     *                     @OA\Property(property="email", type="string", format="email", example="email_example"),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12T20:01:04.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12T20:01:04.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Cierre de sesión exitoso"),
     *             @OA\Property(property="error", type="boolean", example="false")
     *         ),
     *      ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", example={}),
     *             @OA\Property(property="message", type="string", example="Error del servidor"),
     *             @OA\Property(property="error", type="string", example="Detalles internos del error")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        try {
            $user = Auth::user();
            $user->tokens()->delete();

            return $this->successResponse(
                [
                    'user' => $user
                ],
                'Cierre de sesión exitoso',
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

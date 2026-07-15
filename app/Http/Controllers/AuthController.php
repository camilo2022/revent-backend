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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username", "password"},
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 example="revent"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="P4ssw0rd."
     *             )
     *         )
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
     *                     type="object",
     *                     ref="#/components/schemas/User"
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|tokenexampletokenexampletokenexample"),
     *                 @OA\Property(property="expires_in", type="integer", example=7200)
     *             ),
     *             @OA\Property(property="message", type="string", example="Autenticación exitosa")
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
            $user = User::with(['employee' => ['person']])->where('username', $request->input('username'))->firstOrFail();

            if (!Hash::check($request->input('password'), $user->password)) {
                return $this->errorResponse(
                    [
                        'message' => $this->getMessage('UnauthorizedException'),
                        'error' => 'Credenciales incorrectas.'
                    ],
                    422
                );
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $user->load(['roles', 'permissions']);

            return $this->successResponse(
                [
                    'user' => $user,
                    'token' => $token,
                    'expires_in' => config('sanctum.expiration') * 60
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
     *                     type="object",
     *                     ref="#/components/schemas/User"
     *                 ),
     *                 @OA\Property(
     *                     property="navegation",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Module")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Usuario obtenido."),
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
     *                     ref="#/components/schemas/User"
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
            $user->currentAccessToken()->delete();

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

<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="API Laravel Sanctum",
 *         version="1.0.0",
 *         description="Documentación de endpoints de autenticación"
 *     ),
 *     @OA\Server(
 *         url=L5_SWAGGER_CONST_HOST,
 *         description="Servidor API"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Laravel Sanctum authentication"
 * )
 *
 * @OA\Tag(
 *     name="Authorization",
 *     description="Endpoints para gestionar roles y permisos del sistema"
 * )
 *
 * @OA\Tag(
 *     name="Navegation",
 *     description="Endpoints para gestionar módulos y submódulos del sistema"
 * )
 *
 * @OA\Tag(
 *     name="Organizational Structure",
 *     description="Endpoints para gestionar áreas y cargos del sistema"
 * )
 *
 * @OA\Tag(
 *     name="Categorization",
 *     description="Endpoints para gestionar categorías y subcategorías del sistema"
 * )
 *
 * @OA\Tag(
 *     name="Classification",
 *     description="Endpoints para gestionar grupos y subgrupos del sistema"
 * )
 *
 * @OA\Schema(
 *     schema="Position",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="item_id", type="integer", example=4),
 *     @OA\Property(property="name", type="string", example="Super"),
 *     @OA\Property(property="description", type="string", example="Admin"),
 *     @OA\Property(property="settings", type="object", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:31.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T15:37:40.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="area",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="item_id", type="integer", example=4),
 *             @OA\Property(property="name", type="string", example="Super"),
 *             @OA\Property(property="description", type="string", example="Admin"),
 *             @OA\Property(property="settings", type="object", nullable=true),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *             @OA\Property(
 *                 property="pivot",
 *                 type="object",
 *                 @OA\Property(property="model_type", type="string", example="App\\Models\\Position"),
 *                 @OA\Property(property="model_id", type="integer", example=1),
 *                 @OA\Property(property="subitem_id", type="integer", example=1)
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PositionWithRelations",
 *     allOf={
 *            @OA\Schema(ref="#/components/schemas/Position"),
 *            @OA\Schema(
 *                @OA\Property(
 *                    property="roles",
 *                    type="array",
 *                    @OA\Items(
 *                        allOf={
 *                            @OA\Schema(ref="#/components/schemas/Role"),
 *                            @OA\Schema(
 *                                @OA\Property(
 *                                    property="pivot",
 *                                    type="object",
 *                                    @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
 *                                    @OA\Property(property="model_id", type="integer", example=1),
 *                                    @OA\Property(property="role_id", type="integer", example=1)
 *                                )
 *                            )
 *                        }
 *                    )
 *                ),
 *                @OA\Property(
 *                    property="permissions",
 *                    type="array",
 *                    @OA\Items(
 *                        allOf={
 *                            @OA\Schema(ref="#/components/schemas/Permission"),
 *                            @OA\Schema(
 *                                @OA\Property(
 *                                    property="pivot",
 *                                    type="object",
 *                                    @OA\Property(property="model_type", type="string", example="App\\Models\\User"),
 *                                    @OA\Property(property="model_id", type="integer", example=1),
 *                                    @OA\Property(property="role_id", type="integer", example=1)
 *                                )
 *                            )
 *                        }
 *                    )
 *                )
 *            )
 *     }
 * )
 *
 * @OA\Schema(
 *   schema="Role",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="users"),
 *   @OA\Property(property="guard_name", type="string", example="api"),
 *   @OA\Property(property="title", type="string", example="Usuarios."),
 *   @OA\Property(property="description", type="string", example="Gestión de usuarios."),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z")
 * )
 *
 * @OA\Schema(
 *   schema="Permission",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="users"),
 *   @OA\Property(property="guard_name", type="string", example="api"),
 *   @OA\Property(property="title", type="string", example="Usuarios."),
 *   @OA\Property(property="description", type="string", example="Gestión de usuarios."),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z")
 * )
 *
 * @OA\Schema(
 *   schema="UnauthorizedResponse",
 *   type="object",
 *   @OA\Property(property="message", type="string", example="No autenticado."),
 *   @OA\Property(property="error", type="string", example="Unauthenticated.")
 *)
 *
 * @OA\Schema(
 *    schema="ServerErrorResponse",
 *    type="object",
 *    @OA\Property(property="data", type="object"),
 *    @OA\Property(property="message", type="string", example="Error del servidor"),
 *    @OA\Property(property="error", type="string", example="Detalles internos del error")
 *)
 */
class Swagger
{
    // Solo contiene anotaciones
}

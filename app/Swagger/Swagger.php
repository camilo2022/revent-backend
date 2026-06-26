<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="API Laravel Sanctum",
 *         version="1.0.0",
 *         description="Documentación de la API"
 *     ),
 *
 *     @OA\Server(
 *         url=L5_SWAGGER_HOST_DEVELOPMENT,
 *         description="Servidor de Desarrollo"
 *     ),
 *
 *     @OA\Server(
 *         url=L5_SWAGGER_HOST_TESTING,
 *         description="Servidor de Pruebas (QA)"
 *     ),
 *
 *     @OA\Server(
 *         url=L5_SWAGGER_HOST_PRODUCTION,
 *         description="Servidor de Producción"
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

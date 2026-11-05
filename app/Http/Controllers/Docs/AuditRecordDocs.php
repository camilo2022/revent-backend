<?php

namespace App\Http\Controllers\Docs;

/**
 * Documentación Swagger de la auditoría por registro: GET /{módulo}/audits/{id}.
 * Solo describe las rutas (la lógica está en AuditController::record); no se ejecuta.
 */
class AuditRecordDocs
{
    /**
     * @OA\Get(
     *     path="/roles/audits/{id}",
     *     tags={"Authorization - Roles"},
     *     summary="Auditoría de un registro de roles",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function roles() {}

    /**
     * @OA\Get(
     *     path="/permissions/audits/{id}",
     *     tags={"Authorization - Permissions"},
     *     summary="Auditoría de un registro de permisos",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function permissions() {}

    /**
     * @OA\Get(
     *     path="/file_types/audits/{id}",
     *     tags={"File Types"},
     *     summary="Auditoría de un registro de tipos de archivo",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function file_types() {}

    /**
     * @OA\Get(
     *     path="/person_types/audits/{id}",
     *     tags={"Identification - PersonTypes"},
     *     summary="Auditoría de un registro de tipos de persona",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function person_types() {}

    /**
     * @OA\Get(
     *     path="/document_types/audits/{id}",
     *     tags={"Identification - DocumentTypes"},
     *     summary="Auditoría de un registro de tipos de documento",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function document_types() {}

    /**
     * @OA\Get(
     *     path="/blood_types/audits/{id}",
     *     tags={"Blood Types"},
     *     summary="Auditoría de un registro de tipos de sangre",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function blood_types() {}

    /**
     * @OA\Get(
     *     path="/compensation_funds/audits/{id}",
     *     tags={"Compensation Funds"},
     *     summary="Auditoría de un registro de cajas de compensación",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function compensation_funds() {}

    /**
     * @OA\Get(
     *     path="/employees/audits/{id}",
     *     tags={"Employees"},
     *     summary="Auditoría de un registro de empleados",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function employees() {}

    /**
     * @OA\Get(
     *     path="/genders/audits/{id}",
     *     tags={"Genders"},
     *     summary="Auditoría de un registro de géneros",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function genders() {}

    /**
     * @OA\Get(
     *     path="/health_entities/audits/{id}",
     *     tags={"Health Entities"},
     *     summary="Auditoría de un registro de entidades de salud",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function health_entities() {}

    /**
     * @OA\Get(
     *     path="/modules/audits/{id}",
     *     tags={"Navegation - Modules"},
     *     summary="Auditoría de un registro de módulos",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function modules() {}

    /**
     * @OA\Get(
     *     path="/submodules/audits/{id}",
     *     tags={"Navegation - Submodules"},
     *     summary="Auditoría de un registro de submódulos",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function submodules() {}

    /**
     * @OA\Get(
     *     path="/areas/audits/{id}",
     *     tags={"Organizational Structure - Areas"},
     *     summary="Auditoría de un registro de áreas",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function areas() {}

    /**
     * @OA\Get(
     *     path="/positions/audits/{id}",
     *     tags={"Organizational Structure - Positions"},
     *     summary="Auditoría de un registro de cargos",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function positions() {}

    /**
     * @OA\Get(
     *     path="/pension_funds/audits/{id}",
     *     tags={"Pension Funds"},
     *     summary="Auditoría de un registro de fondos de pensión",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function pension_funds() {}

    /**
     * @OA\Get(
     *     path="/people/audits/{id}",
     *     tags={"People"},
     *     summary="Auditoría de un registro de personas",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function people() {}

    /**
     * @OA\Get(
     *     path="/risk_managers/audits/{id}",
     *     tags={"Risk Managers"},
     *     summary="Auditoría de un registro de administradoras de riesgos",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function risk_managers() {}

    /**
     * @OA\Get(
     *     path="/users/audits/{id}",
     *     tags={"Users"},
     *     summary="Auditoría de un registro de usuarios",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function users() {}

    /**
     * @OA\Get(
     *     path="/trademarks/audits/{id}",
     *     tags={"Trademarks"},
     *     summary="Auditoría de un registro de marcas",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function trademarks() {}

    /**
     * @OA\Get(
     *     path="/colors/audits/{id}",
     *     tags={"Colors"},
     *     summary="Auditoría de un registro de colores",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function colors() {}

    /**
     * @OA\Get(
     *     path="/sizes/audits/{id}",
     *     tags={"Sizes"},
     *     summary="Auditoría de un registro de tallas",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function sizes() {}

    /**
     * @OA\Get(
     *     path="/categories/audits/{id}",
     *     tags={"Categorization - Categories"},
     *     summary="Auditoría de un registro de categorías",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function categories() {}

    /**
     * @OA\Get(
     *     path="/collections/audits/{id}",
     *     tags={"Collections"},
     *     summary="Auditoría de un registro de colecciones",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function collections() {}

    /**
     * @OA\Get(
     *     path="/subcategories/audits/{id}",
     *     tags={"Categorization - Subcategories"},
     *     summary="Auditoría de un registro de subcategorías",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function subcategories() {}

    /**
     * @OA\Get(
     *     path="/suppliers/audits/{id}",
     *     tags={"Suppliers"},
     *     summary="Auditoría de un registro de proveedores",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function suppliers() {}

    /**
     * @OA\Get(
     *     path="/stores/audits/{id}",
     *     tags={"Stores"},
     *     summary="Auditoría de un registro de tiendas",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function stores() {}

    /**
     * @OA\Get(
     *     path="/products/audits/{id}",
     *     tags={"Products"},
     *     summary="Auditoría de un registro de productos",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después. También incluye los movimientos de sus colores, tallas y ficha técnica.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function products() {}

    /**
     * @OA\Get(
     *     path="/technical_sheets/audits/{id}",
     *     tags={"TechnicalSheets"},
     *     summary="Auditoría de un registro de fichas técnicas",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después. Incluye las aprobaciones, cancelaciones y cambios de la ficha.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function technical_sheets() {}

    /**
     * @OA\Get(
     *     path="/production_orders/audits/{id}",
     *     tags={"ProductionOrders"},
     *     summary="Auditoría de un registro de órdenes de producción",
     *     description="Historial del registro: quién lo creó, modificó, eliminó o restauró, cuándo y desde dónde (IP, navegador y ruta), con los valores antes y después. También incluye los movimientos de sus detalles por color y de las cantidades por tienda y talla.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Identificador del registro", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="event", in="query", required=false, description="Filtra por tipo de evento", @OA\Schema(type="string", enum={"created","updated","deleted","restored","attach","detach","sync"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Registros por página", @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="page", in="query", required=false, description="N° de página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Historial cargado correctamente", @OA\JsonContent(
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="audits", type="array", @OA\Items(ref="#/components/schemas/Audit")),
     *             @OA\Property(property="meta", type="object")
     *         ),
     *         @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *         @OA\Property(property="error", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=403, description="Sin permiso (audits.find)"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function production_orders() {}
}

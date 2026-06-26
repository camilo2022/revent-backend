<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\EmployeeAllRequest;
use App\Http\Requests\Employee\EmployeeDeleteRequest;
use App\Http\Requests\Employee\EmployeeFindRequest;
use App\Http\Requests\Employee\EmployeeRestoreRequest;
use App\Http\Requests\Employee\EmployeeStoreRequest;
use App\Http\Requests\Employee\EmployeeUpdateRequest;
use App\Http\Resources\Employee\EmployeeCollection;
use App\Http\Resources\Employee\EmployeeResource;
use App\Models\Employee;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Employees",
 *     description="Endpoints para gestionar empleados"
 * )
 *
 * @OA\Schema(
 *     schema="Employee",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="person_id", type="integer", example=1),
 *     @OA\Property(property="position_id", type="integer", example=1),
 *     @OA\Property(property="risk_manager_id", type="integer", example=1),
 *     @OA\Property(property="health_entity_id", type="integer", example=1),
 *     @OA\Property(property="pension_fund_id", type="integer", example=1),
 *     @OA\Property(property="compensation_fund_id", type="integer", example=1),
 *     @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="person",
 *         type="object",
 *         ref="#/components/schemas/Person"
 *     ),
 *     @OA\Property(
 *         property="position",
 *         ref="#/components/schemas/Position"
 *     ),
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         ref="#/components/schemas/User"
 *     ),
 *     @OA\Property(
 *         property="risk_manager",
 *         type="object",
 *         ref="#/components/schemas/RiskManager"
 *     ),
 *     @OA\Property(
 *         property="health_entity",
 *         type="object",
 *         ref="#/components/schemas/HealthEntity"
 *     ),
 *     @OA\Property(
 *         property="pension_fund",
 *         type="object",
 *         ref="#/components/schemas/PensionFund"
 *     ),
 *     @OA\Property(
 *         property="compensation_fund",
 *         type="object",
 *         ref="#/components/schemas/CompensationFund"
 *     )
 * )
 */
class EmployeeController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/employees/all",
     *     tags={"Employees"},
     *     summary="Listar los empleados",
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
     *         @OA\Schema(type="string", enum={"id","person_id","position_id","risk_manager_id","health_entity_id","pension_fund_id","compensation_fund_id","start_date","end_date","created_at","updated_at","deleted_at"}, example="id")
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
     *     @OA\Parameter(
     *         name="with_user",
     *         in="query",
     *         description="Registros con usuarios relacionados.",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de empleados cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "users",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Employee")
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
    public function all(EmployeeAllRequest $request)
    {
        try {
            $employees = Employee::with(['person' => ['photo'], 'position' => ['area'], 'risk_manager', 'health_entity', 'pension_fund', 'compensation_fund', 'user' => ['roles', 'permissions']])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->has('with_user'), function ($query) use ($request) {
                    return $request->boolean('with_user')
                        ? $query->whereHas('user')
                        : $query->whereDoesntHave('user');
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $employees = $employees->paginate($request->integer('per_page', $employees->count()));

            return $this->successResponse(
                new EmployeeCollection($employees),
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
     *     path="/employees/find/{id}",
     *     tags={"Employees"},
     *     summary="Obtener un empleado específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del empleado",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empleado cargado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee",
     *                     ref="#/components/schemas/Employee"
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
    public function find(EmployeeFindRequest $request, $id)
    {
        try {
            $employee = Employee::with(['person' => ['photo'], 'position' => ['area'], 'risk_manager', 'health_entity', 'pension_fund', 'compensation_fund', 'user' => ['roles', 'permissions']])
                ->findOrFail($id);

            return $this->successResponse(
                new EmployeeResource($employee),
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
     *     path="/employees/store",
     *     tags={"Employees"},
     *     summary="Crear un empleado",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"person_id","position_id","risk_manager_id","health_entity_id","pension_fund_id","compensation_fund_id","start_date"},
     *             @OA\Property(property="person_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Identificador de la persona"
     *             ),
     *             @OA\Property(
     *                 property="position_id",
     *                 type="integer",
     *                 example=10,
     *                 description="Identificador del cargo"
     *             ),
     *             @OA\Property(
     *                 property="risk_manager_id",
     *                 type="integer",
     *                 example=7,
     *                 description="Identificador de la administradora de riesgos"
     *             ),
     *             @OA\Property(
     *                 property="health_entity_id",
     *                 type="integer",
     *                 example=5,
     *                 description="Identificador de la entidad de salud"
     *             ),
     *             @OA\Property(
     *                 property="pension_fund_id",
     *                 type="integer",
     *                 example=3,
     *                 description="Identificador del fondo de pensión"
     *             ),
     *             @OA\Property(
     *                 property="compensation_fund_id",
     *                 type="integer",
     *                 example=2,
     *                 description="Identificador de la caja de compensación"
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 example="2026-04-01",
     *                 description="Fecha de inicio (Y-m-d)"
     *             ),
     *             @OA\Property(
     *                 property="end_date",
     *                 type="string",
     *                 format="date",
     *                 nullable=true,
     *                 example="2026-12-31",
     *                 description="Fecha de finalización (debe ser mayor a start_date)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empleado creado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee",
     *                     type="object",
     *                     @OA\Property(property="person_id", type="integer", example=1),
     *                     @OA\Property(property="position_id", type="integer", example=1),
     *                     @OA\Property(property="risk_manager_id", type="integer", example=1),
     *                     @OA\Property(property="health_entity_id", type="integer", example=1),
     *                     @OA\Property(property="pension_fund_id", type="integer", example=1),
     *                     @OA\Property(property="compensation_fund_id", type="integer", example=1),
     *                     @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="id", type="integer", example=1),
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
     *                  @OA\Property(property="person_id", type="string", example="Persona"),
     *                  @OA\Property(property="position_id", type="string", example="Cargo"),
     *                  @OA\Property(property="risk_manager_id", type="string", example="Administradora de Riesgos"),
     *                  @OA\Property(property="health_entity_id", type="string", example="Entidad de Salud"),
     *                  @OA\Property(property="pension_fund_id", type="string", example="Fondo de pensión"),
     *                  @OA\Property(property="compensation_fund_id", type="string", example="Caja de compensación"),
     *                  @OA\Property(property="start_date", type="string", example="Fecha inicio"),
     *                  @OA\Property(property="end_date", type="string", example="Fecha fin")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="person_id",
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
    public function store(EmployeeStoreRequest $request)
    {
        try {
            $employee = new Employee();
            $employee->person_id = $request->integer('person_id');
            $employee->position_id = $request->integer('position_id');
            $employee->risk_manager_id = $request->integer('risk_manager_id');
            $employee->health_entity_id = $request->integer('health_entity_id');
            $employee->pension_fund_id = $request->integer('pension_fund_id');
            $employee->compensation_fund_id = $request->integer('compensation_fund_id');
            $employee->start_date = $request->input('start_date');
            $employee->end_date = $request->input('end_date', null);
            $employee->save();

            return $this->successResponse(
                new EmployeeResource($employee),
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
     *     path="/employees/update/{id}",
     *     tags={"Employees"},
     *     summary="Editar un empleado específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del empleado",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"person_id","position_id","risk_manager_id","health_entity_id","pension_fund_id","compensation_fund_id","start_date"},
     *             @OA\Property(
     *                 property="position_id",
     *                 type="integer",
     *                 example=10,
     *                 description="Identificador del cargo"
     *             ),
     *             @OA\Property(
     *                 property="risk_manager_id",
     *                 type="integer",
     *                 example=7,
     *                 description="Identificador de la administradora de riesgos"
     *             ),
     *             @OA\Property(
     *                 property="health_entity_id",
     *                 type="integer",
     *                 example=5,
     *                 description="Identificador de la entidad de salud"
     *             ),
     *             @OA\Property(
     *                 property="pension_fund_id",
     *                 type="integer",
     *                 example=3,
     *                 description="Identificador del fondo de pensión"
     *             ),
     *             @OA\Property(
     *                 property="compensation_fund_id",
     *                 type="integer",
     *                 example=2,
     *                 description="Identificador de la caja de compensación"
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 example="2026-04-01",
     *                 description="Fecha de inicio (Y-m-d)"
     *             ),
     *             @OA\Property(
     *                 property="end_date",
     *                 type="string",
     *                 format="date",
     *                 nullable=true,
     *                 example="2026-12-31",
     *                 description="Fecha de finalización (debe ser mayor a start_date)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empleado editado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="person_id", type="integer", example=1),
     *                     @OA\Property(property="position_id", type="integer", example=1),
     *                     @OA\Property(property="risk_manager_id", type="integer", example=1),
     *                     @OA\Property(property="health_entity_id", type="integer", example=1),
     *                     @OA\Property(property="pension_fund_id", type="integer", example=1),
     *                     @OA\Property(property="compensation_fund_id", type="integer", example=1),
     *                     @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
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
     *                  @OA\Property(property="person_id", type="string", example="Persona"),
     *                  @OA\Property(property="position_id", type="string", example="Cargo"),
     *                  @OA\Property(property="risk_manager_id", type="string", example="Administradora de Riesgos"),
     *                  @OA\Property(property="health_entity_id", type="string", example="Entidad de Salud"),
     *                  @OA\Property(property="pension_fund_id", type="string", example="Fondo de pensión"),
     *                  @OA\Property(property="compensation_fund_id", type="string", example="Caja de compensación"),
     *                  @OA\Property(property="start_date", type="string", example="Fecha inicio"),
     *                  @OA\Property(property="end_date", type="string", example="Fecha fin")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="person_id",
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
    public function update(EmployeeUpdateRequest $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->person_id = $request->integer('person_id');
            $employee->position_id = $request->integer('position_id');
            $employee->risk_manager_id = $request->integer('risk_manager_id');
            $employee->health_entity_id = $request->integer('health_entity_id');
            $employee->pension_fund_id = $request->integer('pension_fund_id');
            $employee->compensation_fund_id = $request->integer('compensation_fund_id');
            $employee->start_date = $request->input('start_date');
            $employee->end_date = $request->input('end_date', null);
            $employee->save();

            return $this->successResponse(
                new EmployeeResource($employee),
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
     *     path="/employees/delete/{id}",
     *     tags={"Employees"},
     *     summary="Desactivar un empleado específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del empleado",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empleado desactivado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="person_id", type="integer", example=1),
     *                     @OA\Property(property="position_id", type="integer", example=1),
     *                     @OA\Property(property="risk_manager_id", type="integer", example=1),
     *                     @OA\Property(property="health_entity_id", type="integer", example=1),
     *                     @OA\Property(property="pension_fund_id", type="integer", example=1),
     *                     @OA\Property(property="compensation_fund_id", type="integer", example=1),
     *                     @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", example="2026-03-12 20:01:03")
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
     *                 @OA\Property(property="id", type="string", example="Identificador del empleado")
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
    public function delete(EmployeeDeleteRequest $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->delete();

            return $this->successResponse(
                new EmployeeResource($employee),
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
     *     path="/employees/restore/{id}",
     *     tags={"Employees"},
     *     summary="Activar un empleado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del empleado",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empleado activado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="clothing",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="person_id", type="integer", example=1),
     *                     @OA\Property(property="position_id", type="integer", example=1),
     *                     @OA\Property(property="risk_manager_id", type="integer", example=1),
     *                     @OA\Property(property="health_entity_id", type="integer", example=1),
     *                     @OA\Property(property="pension_fund_id", type="integer", example=1),
     *                     @OA\Property(property="compensation_fund_id", type="integer", example=1),
     *                     @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-12 20:01:03"),
     *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
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
     *                 @OA\Property(property="id", type="string", example="Identificador del empleado")
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
    public function restore(EmployeeRestoreRequest $request, $id)
    {
        try {
            $employee = Employee::withTrashed()->findOrFail($id);
            $employee->restore();

            return $this->successResponse(
                new EmployeeResource($employee),
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

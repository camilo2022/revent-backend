<?php

namespace App\Http\Controllers;

use App\Exports\PeopleExport;
use App\Http\Requests\Person\PersonAllRequest;
use App\Http\Requests\Person\PersonDeleteRequest;
use App\Http\Requests\Person\PersonFindRequest;
use App\Http\Requests\Person\PersonRestoreRequest;
use App\Http\Requests\Person\PersonStoreRequest;
use App\Http\Requests\Person\PersonUpdateRequest;
use App\Http\Resources\Person\PersonCollection;
use App\Http\Resources\Person\PersonResource;
use App\Imports\PeopleImport;
use App\Models\Person;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="People",
 *     description="Endpoints para gestionar personas"
 * )
 *
 * @OA\PathItem(
 *     path="/people",
 *     description="Rutas de gestión de personas"
 * )
 *
 * @OA\Schema(
 *     schema="Person",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="document", type="integer", example=10000001),
 *     @OA\Property(property="names", type="string", example="NAME_EXAMPLE"),
 *     @OA\Property(property="last_names", type="integer", example="NAME_LAST_NAMES"),
 *     @OA\Property(property="birth_date", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="address", type="string", example="Calle 7"),
 *     @OA\Property(property="phone", type="string", example="0001254"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="gender",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=25),
 *         @OA\Property(property="item_id", type="integer", example=7),
 *         @OA\Property(property="name", type="string", example="NAME_GENDER"),
 *         @OA\Property(property="description", type="string", example="DESCRIPTION_GENDER"),
 *         @OA\Property(property="settings", type="object", example={}),
 *         @OA\Property(property="created_at",type="string",format="date-time",example="2026-03-30T13:17:34.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:34.000000Z"),
 *         @OA\Property(property="deleted_at",type="string",format="date-time",nullable=true,example=null)
 *     ),
 *     @OA\Property(
 *         property="photo",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=2),
 *         @OA\Property(property="model_type", type="string", example="App\\Models\\Person"),
 *         @OA\Property(property="model_id", type="integer", example=2),
 *         @OA\Property(property="name", type="string", example="2"),
 *         @OA\Property(property="file_type_id", type="integer", example=1),
 *         @OA\Property(property="file_subtype_id", type="integer", example=8),
 *         @OA\Property(property="path", type="string", format="uri", example="person/file.jpg"),
 *         @OA\Property(property="mime", type="string", example="image/jpeg"),
 *         @OA\Property(property="extension", type="string", example="jpg"),
 *         @OA\Property(property="size", type="integer", example=12870),
 *         @OA\Property(
 *             property="metadata",
 *             type="object",
 *             @OA\Property(property="mime", type="string", example="image/jpeg"),
 *             @OA\Property(property="size", type="integer", example=12870),
 *             @OA\Property(
 *                 property="image",
 *                 type="object",
 *                 @OA\Property(property="width", type="integer", example=128),
 *                 @OA\Property(property="height", type="integer", example=128)
 *             ),
 *             @OA\Property(property="hash_md5", type="string", example="eea1251373716a2da44ec80919fc4a33"),
 *             @OA\Property(property="extension", type="string", example="jpg"),
 *             @OA\Property(property="client_mime", type="string", example="image/jpeg"),
 *             @OA\Property(property="hash_sha256", type="string", example="913be661c7387b1c60f9c5eed3c0a37c1ab9dff5625ea6c7a39fe7909b0a86b8"),
 *             @OA\Property(property="uploaded_at", type="string", format="date-time", example="2026-03-31 18:58:06"),
 *             @OA\Property(property="original_name", type="string", example="2.jpg"),
 *             @OA\Property(property="original_extension", type="string", example="jpg")
 *         ),
 *         @OA\Property(property="settings", type="object", example={}),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T19:19:35.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-31T18:58:06.000000Z")
 *     ),
 *     @OA\Property(
 *         property="blood_type",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=25),
 *         @OA\Property(property="item_id", type="integer", example=7),
 *         @OA\Property(property="name", type="string", example="NAME_EXAMPLE"),
 *         @OA\Property(property="description", type="string", example="DESCRIPTION_EXAMPLE"),
 *         @OA\Property(property="settings", type="object", example={}),
 *         @OA\Property(property="created_at",type="string",format="date-time",example="2026-03-30T13:17:34.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-03-30T13:17:34.000000Z"),
 *         @OA\Property(property="deleted_at",type="string",format="date-time",nullable=true,example=null)
 *     ),
 *     @OA\Property(
 *         property="person",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="person_id", type="integer", example=1),
 *         @OA\Property(property="operation_center", type="string", example="001"),
 *         @OA\Property(property="position_id", type="integer", example=1),
 *         @OA\Property(property="risk_manager_id", type="integer", example=1),
 *         @OA\Property(property="health_entity_id", type="integer", example=1),
 *         @OA\Property(property="pension_fund_id", type="integer", example=1),
 *         @OA\Property(property="compensation_fund_id", type="integer", example=1),
 *         @OA\Property(property="start_date", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     )
 * )
 */
class PersonController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/people/all",
     *     tags={"People"},
     *     summary="Listar las personas",
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
     *         @OA\Schema(type="string", enum={"id","document","names","last_names","gender_id","birth_date","blood_type_id","address","phone","created_at","updated_at","deleted_at"}, example="names")
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
     *         description="Lista de personas cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "users",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Person")
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
    public function all(PersonAllRequest $request)
    {
        try {
            $people = Person::with(['gender', 'blood_type', 'employee', 'photo'])
                ->when($request->boolean('with_employee'), function ($query) use ($request) {
                    return $query;
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) use ($request) {
                    return $query->withTrashed();
                })
                ->when($request->has('with_employee'), function ($query) use ($request) {
                    return $request->boolean('with_employee')
                        ? $query->whereHas('employee', fn($q) => $q->withTrashed())
                        : $query->whereDoesntHave('employee', fn($q) => $q->withTrashed());
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $people = $people->paginate($request->integer('per_page', $people->count()));

            return $this->successResponse(
                new PersonCollection($people),
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
     *     path="/people/find/{id}",
     *     tags={"People"},
     *     summary="Obtener una persona específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la persona",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Persona cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="person",
     *                     ref="#/components/schemas/Person"
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
    public function find(PersonFindRequest $request, $id)
    {
        try {
            $person = Person::with(['gender', 'blood_type', 'employee', 'photo'])
                ->findOrFail($id);

            return $this->successResponse(
                new PersonResource($person),
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
     *     path="/people/store",
     *     tags={"People"},
     *     summary="Crear una persona",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"document","names","last_names","gender_id","birth_date","blood_type_id","address","phone","photo_type_id","photo_subtype_id"},
     *                 @OA\Property(
     *                     property="document",
     *                     type="string",
     *                     example="123456789"
     *                 ),
     *                 @OA\Property(
     *                     property="names",
     *                     type="string",
     *                     example="Zaray"
     *                 ),
     *                 @OA\Property(
     *                     property="last_names",
     *                     type="string",
     *                     example="Cortez Castro"
     *                 ),
     *                 @OA\Property(
     *                     property="gender_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="birth_date",
     *                     type="string",
     *                     format="date",
     *                     example="2000-01-01"
     *                 ),
     *                 @OA\Property(
     *                     property="blood_type_id",
     *                     type="integer",
     *                     example=2
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="Calle 123 #45-67"
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     example="3001234567"
     *                 ),
     *                 @OA\Property(
     *                     property="photo_type_id",
     *                     type="string",
     *                     example="1"
     *                 ),
     *                 @OA\Property(
     *                     property="photo_subtype_id",
     *                     type="string",
     *                     example="8"
     *                 ),
     *                 @OA\Property(
     *                     property="photo",
     *                     type="string",
     *                     format="binary",
     *                     nullable=true,
     *                     description="Archivo de imagen (jpg, png, etc.)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Persona creada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="person",
     *                     type="object",
     *                     @OA\Property(property="document", type="integer", example=10000001),
     *                     @OA\Property(property="names", type="string", example="NAME_EXAMPLE"),
     *                     @OA\Property(property="last_names", type="integer", example="NAME_LAST_NAMES"),
     *                     @OA\Property(property="birth_date", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="address", type="string", example="Calle 7"),
     *                     @OA\Property(property="phone", type="string", example="0001254"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
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
     *                  @OA\Property(property="document", type="string", example="Número de documento"),
     *                  @OA\Property(property="names", type="string", example="Nombres"),
     *                  @OA\Property(property="last_names", type="string", example="Apellidos"),
     *                  @OA\Property(property="gender_id", type="string", example="Genero"),
     *                  @OA\Property(property="birth_date", type="string", example="Fecha de nacimiento"),
     *                  @OA\Property(property="blood_type_id", type="string", example="Tipo de sangre"),
     *                  @OA\Property(property="address", type="string", example="Dirección"),
     *                  @OA\Property(property="phone", type="string", example="Número de teléfono"),
     *                  @OA\Property(property="photo", type="string", example="Foto")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document",
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
    public function store(PersonStoreRequest $request)
    {
        try {
            $person = new Person();
            $person->document = $request->input('document');
            $person->names = $request->input('names');
            $person->last_names = $request->input('last_names');
            $person->gender_id = $request->integer('gender_id');
            $person->birth_date = $request->input('birth_date');
            $person->blood_type_id = $request->integer('blood_type_id');
            $person->address = $request->input('address');
            $person->phone = $request->input('phone');
            $person->save();

            if ($request->hasFile('photo')) {
                app(FileService::class)->save(
                    $person,
                    $request->file('photo'),
                    $request->integer('photo_type_id'),
                    $request->integer('photo_subtype_id'),
                    'public',
                    'person'
                );
            }

            return $this->successResponse(
                new PersonResource($person),
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
     *     path="/people/update/{id}",
     *     tags={"People"},
     *     summary="Editar la información de una persona en específico",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"document","names","last_names","gender_id","birth_date","blood_type_id","address","phone"},
     *                 @OA\Property(
     *                     property="document",
     *                     type="string",
     *                     example="123456789"
     *                 ),
     *                 @OA\Property(
     *                     property="names",
     *                     type="string",
     *                     example="Zaray"
     *                 ),
     *                 @OA\Property(
     *                     property="last_names",
     *                     type="string",
     *                     example="Cortez Castro"
     *                 ),
     *                 @OA\Property(
     *                     property="gender_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="birth_date",
     *                     type="string",
     *                     format="date",
     *                     example="2000-01-01"
     *                 ),
     *                 @OA\Property(
     *                     property="blood_type_id",
     *                     type="integer",
     *                     example=2
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="Calle 123 #45-67"
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="string",
     *                     example="3001234567"
     *                 ),
     *                 @OA\Property(
     *                     property="photo",
     *                     type="string",
     *                     format="binary",
     *                     nullable=true,
     *                     description="Archivo de imagen (jpg, png, etc.)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Persona editada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="person",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="document", type="integer", example=10000001),
     *                     @OA\Property(property="names", type="string", example="NAME_EXAMPLE"),
     *                     @OA\Property(property="last_names", type="integer", example="NAME_LAST_NAMES"),
     *                     @OA\Property(property="birth_date", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="address", type="string", example="Calle 7"),
     *                     @OA\Property(property="phone", type="string", example="0001254"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
     *                     @OA\Property(property="deleted_at",type="string",format="date-time",nullable=true,example=null)
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
     *                  @OA\Property(property="document", type="string", example="Número de documento"),
     *                  @OA\Property(property="names", type="string", example="Nombres"),
     *                  @OA\Property(property="last_names", type="string", example="Apellidos"),
     *                  @OA\Property(property="gender_id", type="string", example="Genero"),
     *                  @OA\Property(property="birth_date", type="string", example="Fecha de nacimiento"),
     *                  @OA\Property(property="blood_type_id", type="string", example="Tipo de sangre"),
     *                  @OA\Property(property="address", type="string", example="Dirección"),
     *                  @OA\Property(property="phone", type="string", example="Número de teléfono"),
     *                  @OA\Property(property="photo", type="string", example="Foto")
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="document",
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
    public function update(PersonUpdateRequest $request, $id)
    {
        try {
            $person = Person::findOrFail($id);
            $person->document = $request->input('document');
            $person->names = $request->input('names');
            $person->last_names = $request->input('last_names');
            $person->gender_id = $request->integer('gender_id');
            $person->birth_date = $request->input('birth_date');
            $person->blood_type_id = $request->integer('blood_type_id');
            $person->address = $request->input('address');
            $person->phone = $request->input('phone');
            $person->save();

            if ($request->hasFile('photo')) {
                app(FileService::class)->save(
                    $person,
                    $request->file('photo'),
                    $request->integer('photo_type_id'),
                    $request->integer('photo_subtype_id'),
                    'public',
                    'person'
                );
            }

            return $this->successResponse(
                new PersonResource($person),
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
     *     path="/people/delete/{id}",
     *     tags={"People"},
     *     summary="Desactivar una persona específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la persona",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Persona desactivada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="document", type="integer", example=10000001),
     *                     @OA\Property(property="names", type="string", example="NAME_EXAMPLE"),
     *                     @OA\Property(property="last_names", type="integer", example="NAME_LAST_NAMES"),
     *                     @OA\Property(property="birth_date", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="address", type="string", example="Calle 7"),
     *                     @OA\Property(property="phone", type="string", example="0001254"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
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
     *                 @OA\Property(property="id", type="string", example="Identificador de la persona")
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
    public function delete(PersonDeleteRequest $request, $id)
    {
        try {
            $person = Person::findOrFail($id);
            $person->delete();

            return $this->successResponse(
                new PersonResource($person),
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
     *     path="/people/remove/{id}",
     *     tags={"People"},
     *     summary="Activar una persona específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador de la persona",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Persona activada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="document", type="integer", example=10000001),
     *                     @OA\Property(property="names", type="string", example="NAME_EXAMPLE"),
     *                     @OA\Property(property="last_names", type="integer", example="NAME_LAST_NAMES"),
     *                     @OA\Property(property="birth_date", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="address", type="string", example="Calle 7"),
     *                     @OA\Property(property="phone", type="string", example="0001254"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
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
     *                 @OA\Property(property="id", type="string", example="Identificador de la persona")
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
    public function restore(PersonRestoreRequest $request, $id)
    {
        try {
            $person = Person::withTrashed()->findOrFail($id);
            $person->restore();

            return $this->successResponse(
                new PersonResource($person),
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

    public function pdf()
    {
        try {
            $people = Person::all();
            $pdf = Pdf::loadView('person', compact('people'))->setPaper('letter');
            return $pdf->download('people.pdf');
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

    public function excel()
    {
        return Excel::download(new PeopleExport, 'people.xlsx');
    }

    public function import(Request $request)
    {
        Excel::import(new PeopleImport, $request->file('file'));

        return response()->json([
            'message' => 'Importación exitosa'
        ]);
    }
}

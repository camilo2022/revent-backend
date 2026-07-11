<?php

namespace App\Http\Controllers;

use App\Http\Requests\Person\PersonAllRequest;
use App\Http\Requests\Person\PersonDeleteRequest;
use App\Http\Requests\Person\PersonFindRequest;
use App\Http\Requests\Person\PersonRestoreRequest;
use App\Http\Requests\Person\PersonStoreRequest;
use App\Http\Requests\Person\PersonUpdateRequest;
use App\Http\Resources\Person\PersonCollection;
use App\Http\Resources\Person\PersonResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\Person;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @OA\Tag(
 *     name="People",
 *     description="Endpoints para gestionar personas"
 * )
 *
 * @OA\Schema(
 *     schema="Person",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="names", type="string", example="Nombre"),
 *     @OA\Property(property="last_names", type="string", example="Apellido"),
 *     @OA\Property(property="document_type_id", type="integer", example=1),
 *     @OA\Property(property="document", type="integer", example=10000001),
 *     @OA\Property(property="gender_id", type="integer", example=1),
 *     @OA\Property(property="birth_date", format="date", example="2026-03-30"),
 *     @OA\Property(property="blood_type_id", type="integer", example=1),
 *     @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
 *     @OA\Property(property="location_id", type="integer", example=1),
 *     @OA\Property(property="address", type="string", example="Calle 7"),
 *     @OA\Property(property="phone_country_id", type="integer", example=1),
 *     @OA\Property(property="phone", type="string", example="3222759176"),
 *     @OA\Property(property="email", type="string", format="email", example="example@example.com"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(
 *         property="document_type",
 *         type="object",
 *         ref="#/components/schemas/DocumentType"
 *     ),
 *     @OA\Property(
 *         property="gender",
 *         type="object",
 *         ref="#/components/schemas/Gender"
 *     ),
 *     @OA\Property(
 *         property="blood_type",
 *         type="object",
 *         ref="#/components/schemas/BloodType"
 *     ),
 *     @OA\Property(
 *         property="location",
 *         discriminator=@OA\Discriminator(
 *             propertyName="type",
 *             mapping={
 *                 "country"="#/components/schemas/Country",
 *                 "department"="#/components/schemas/Department",
 *                 "city"="#/components/schemas/City"
 *             }
 *         ),
 *         oneOf={
 *             @OA\Schema(ref="#/components/schemas/Country"),
 *             @OA\Schema(ref="#/components/schemas/Department"),
 *             @OA\Schema(ref="#/components/schemas/City")
 *         }
 *     ),
 *     @OA\Property(
 *         property="phone_country",
 *         type="object",
 *         ref="#/components/schemas/Country"
 *     ),
 *     @OA\Property(
 *         property="photo",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=2),
 *         @OA\Property(property="model_type", type="string", example="App\\Models\\Person"),
 *         @OA\Property(property="model_id", type="integer", example=2),
 *         @OA\Property(property="name", type="string", example="2"),
 *         @OA\Property(property="file_type_id", type="integer", example=1),
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
 *         property="employee",
 *         type="object",
 *         ref="#/components/schemas/Employee"
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
     *         @OA\Schema(type="string", enum={"id","names","last_names","document_type_id","document","gender_id","birth_date","blood_type_id","address","phone","created_at","updated_at","deleted_at"}, example="names")
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
            $people = Person::with([
                    'location' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        Country::class => ['region' => ['continent']],
                        Department::class => ['country' => ['region' => ['continent']]],
                        City::class => ['department' => ['country' => ['region' => ['continent']]]],
                    ]),
                    'document_type' => ['person_type'], 'gender', 'blood_type', 'phone_country', 'employee', 'photo' => ['file_type']
                ])
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
            $person = Person::with([
                    'location' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        Country::class => ['region' => ['continent']],
                        Department::class => ['country' => ['region' => ['continent']]],
                        City::class => ['department' => ['country' => ['region' => ['continent']]]],
                    ]),
                    'document_type' => ['person_type'], 'gender', 'blood_type', 'phone_country', 'employee', 'photo' => ['file_type']
                ])->findOrFail($id);

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
     *                 required={"names","last_names","document_type_id","document","gender_id","birth_date","blood_type_id","address","phone","file_type_id"},
     *                 @OA\Property(property="names", type="string", example="Nombre"),
     *                 @OA\Property(property="last_names", type="string", example="Apellido"),
     *                 @OA\Property(property="document_type_id", type="integer", example=1),
     *                 @OA\Property(property="document", type="string", example="123456789"),
     *                 @OA\Property(property="gender_id", type="integer", example=1),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="2000-01-01"),
     *                 @OA\Property(property="blood_type_id", type="integer", example=2),
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                 @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *                 @OA\Property(property="neighborhood", type="string", example="Barrio XYZ"),
     *                 @OA\Property(property="phone", type="string", example="3001234567"),
     *                 @OA\Property(property="file_type_id", type="string", example="1"),
     *                 @OA\Property(property="photo", type="string", format="binary", nullable=true, description="Archivo de imagen (jpg, png, etc.)")
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
     *                     ref="#/components/schemas/Person"
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
     *                  @OA\Property(property="names", type="string", example="Nombres"),
     *                  @OA\Property(property="last_names", type="string", example="Apellidos"),
     *                  @OA\Property(property="document_type_id", type="string", example="Tipo de documento"),
     *                  @OA\Property(property="document", type="string", example="Número de documento"),
     *                  @OA\Property(property="gender_id", type="string", example="Genero"),
     *                  @OA\Property(property="birth_date", type="string", example="Fecha de nacimiento"),
     *                  @OA\Property(property="blood_type_id", type="string", example="Tipo de sangre"),
     *                  @OA\Property(property="location_id", type="string", example="Ubicación"),
     *                  @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                  @OA\Property(property="address", type="string", example="Dirección"),
     *                  @OA\Property(property="neighborhood", type="string", example="Barrio"),
     *                  @OA\Property(property="phone_country_id", type="string", example="País del código de teléfono"),
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
            $person->names = $request->input('names');
            $person->last_names = $request->input('last_names');
            $person->document_type_id = $request->integer('document_type_id');
            $person->document = $request->input('document');
            $person->gender_id = $request->integer('gender_id');
            $person->birth_date = $request->input('birth_date');
            $person->blood_type_id = $request->integer('blood_type_id');
            $person->location_id = $request->integer('location_id');
            $person->location_type = $request->input('location_type');
            $person->address = $request->input('address');
            $person->neighborhood = $request->input('neighborhood');
            $person->phone_country_id = $request->integer('phone_country_id');
            $person->phone = $request->input('phone');
            $person->email = $request->input('email');
            $person->save();

            if ($request->hasFile('photo')) app(FileService::class)->save($person, $request->file('photo'), $request->integer('photo_type_id'), 'public', 'person');

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
     *                 required={"names","last_names","document_type_id","document","gender_id","birth_date","blood_type_id","address","phone","file_type_id"},
     *                 @OA\Property(property="names", type="string", example="Nombre"),
     *                 @OA\Property(property="last_names", type="string", example="Apellido"),
     *                 @OA\Property(property="document_type_id", type="integer", example=1),
     *                 @OA\Property(property="document", type="string", example="123456789"),
     *                 @OA\Property(property="gender_id", type="integer", example=1),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="2000-01-01"),
     *                 @OA\Property(property="blood_type_id", type="integer", example=2),
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                 @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *                 @OA\Property(property="neighborhood", type="string", example="Barrio XYZ"),
     *                 @OA\Property(property="phone", type="string", example="3001234567"),
     *                 @OA\Property(property="file_type_id", type="string", example="1"),
     *                 @OA\Property(property="photo", type="string", format="binary", nullable=true, description="Archivo de imagen (jpg, png, etc.)")
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
     *                     ref="#/components/schemas/Person"
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
     *                  property="attributes",
     *                  type="object",
     *                  @OA\Property(property="names", type="string", example="Nombres"),
     *                  @OA\Property(property="last_names", type="string", example="Apellidos"),
     *                  @OA\Property(property="document_type_id", type="string", example="Tipo de documento"),
     *                  @OA\Property(property="document", type="string", example="Número de documento"),
     *                  @OA\Property(property="gender_id", type="string", example="Genero"),
     *                  @OA\Property(property="birth_date", type="string", example="Fecha de nacimiento"),
     *                  @OA\Property(property="blood_type_id", type="string", example="Tipo de sangre"),
     *                  @OA\Property(property="location_id", type="string", example="Ubicación"),
     *                  @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                  @OA\Property(property="address", type="string", example="Dirección"),
     *                  @OA\Property(property="neighborhood", type="string", example="Barrio"),
     *                  @OA\Property(property="phone_country_id", type="string", example="País del código de teléfono"),
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
            $person->names = $request->input('names');
            $person->last_names = $request->input('last_names');
            $person->document_type_id = $request->integer('document_type_id');
            $person->document = $request->input('document');
            $person->gender_id = $request->integer('gender_id');
            $person->birth_date = $request->input('birth_date');
            $person->blood_type_id = $request->integer('blood_type_id');
            $person->location_id = $request->integer('location_id');
            $person->location_type = $request->input('location_type');
            $person->address = $request->input('address');
            $person->neighborhood = $request->input('neighborhood');
            $person->phone_country_id = $request->integer('phone_country_id');
            $person->phone = $request->input('phone');
            $person->email = $request->input('email');
            $person->save();

            if ($request->hasFile('photo')) app(FileService::class)->save($person, $request->file('photo'), $request->integer('photo_type_id'), 'public', 'person');

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
     *                     property="person",
     *                     type="object",
     *                     ref="#/components/schemas/Person"
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
     *                     property="person",
     *                     type="object",
     *                     ref="#/components/schemas/Person"
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
}

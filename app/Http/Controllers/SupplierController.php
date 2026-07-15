<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\SupplierAllRequest;
use App\Http\Requests\Supplier\SupplierDeleteRequest;
use App\Http\Requests\Supplier\SupplierFindRequest;
use App\Http\Requests\Supplier\SupplierRestoreRequest;
use App\Http\Requests\Supplier\SupplierStoreRequest;
use App\Http\Requests\Supplier\SupplierUpdateRequest;
use App\Http\Resources\Supplier\SupplierCollection;
use App\Http\Resources\Supplier\SupplierResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\Supplier;
use App\Services\FileService;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @OA\Tag(
 *     name="Suppliers",
 *     description="Endpoints para gestionar proveedores"
 * )
 *
 * @OA\Schema(
 *     schema="Supplier",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="00"),
 *     @OA\Property(property="legal_name", type="string", example="Nombre legal"),
 *     @OA\Property(property="trade_name", type="string", example="Nombre comercial"),
 *     @OA\Property(property="document_type_id", type="integer", example=1),
 *     @OA\Property(property="document", type="integer", example=10000001),
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
 *         property="file",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=2),
 *         @OA\Property(property="model_type", type="string", example="App\\Models\\Supplier"),
 *         @OA\Property(property="model_id", type="integer", example=2),
 *         @OA\Property(property="name", type="string", example="2"),
 *         @OA\Property(property="file_type_id", type="integer", example=1),
 *         @OA\Property(property="path", type="string", format="uri", example="supplier/file.jpg"),
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
class SupplierController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/suppliers/all",
     *     tags={"Suppliers"},
     *     summary="Listar los proveedores",
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
     *         @OA\Schema(type="string", enum={"id","code","legal_name","trade_name","document_type_id","document","address","phone","created_at","updated_at","deleted_at"}, example="legal_name")
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
     *         description="Lista de proveedores cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property= "users",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Supplier")
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
    public function all(SupplierAllRequest $request)
    {
        try {
            $suppliers = Supplier::with([
                    'location' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        Country::class => ['region' => ['continent']],
                        Department::class => ['country' => ['region' => ['continent']]],
                        City::class => ['department' => ['country' => ['region' => ['continent']]]],
                    ]),
                    'document_type' => ['person_type'], 'phone_country'
                ])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) use ($request) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $suppliers = $suppliers->paginate($request->integer('per_page', $suppliers->count()));

            return $this->successResponse(
                new SupplierCollection($suppliers),
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
     *     path="/suppliers/find/{id}",
     *     tags={"Suppliers"},
     *     summary="Obtener un proveedor específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del proveedor",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suppliera cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     ref="#/components/schemas/Supplier"
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
    public function find(SupplierFindRequest $request, $id)
    {
        try {
            $supplier = Supplier::with([
                    'location' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        Country::class => ['region' => ['continent']],
                        Department::class => ['country' => ['region' => ['continent']]],
                        City::class => ['department' => ['country' => ['region' => ['continent']]]],
                    ]),
                    'document_type' => ['person_type'], 'employee'
                ])->findOrFail($id);

            return $this->successResponse(
                new SupplierResource($supplier),
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
     *     path="/suppliers/store",
     *     tags={"Suppliers"},
     *     summary="Crear un proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"code","legal_name","trade_name","document_type_id","document","location_id","location_type","address","neighborhood","phone_country_id","phone"},
     *                 @OA\Property(property="code", type="string", example="00"),
     *                 @OA\Property(property="legal_name", type="string", example="Nombre legal"),
     *                 @OA\Property(property="trade_name", type="string", example="Nombre comercial"),
     *                 @OA\Property(property="document_type_id", type="integer", example=1),
     *                 @OA\Property(property="document", type="string", example="123456789"),
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                 @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *                 @OA\Property(property="neighborhood", type="string", example="Barrio XYZ"),
     *                 @OA\Property(property="phone", type="string", example="3001234567"),
     *                 @OA\Property(property="file_type_id", type="string", example="1"),
     *                 @OA\Property(property="file", type="string", format="binary", nullable=true, description="Archivo (pdf, doc, etc.)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suppliera creada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     type="object",
     *                     ref="#/components/schemas/Supplier"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
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
     *                  @OA\Property(property="code", type="string", example="00"),
     *                  @OA\Property(property="legal_name", type="string", example="Nombre legal"),
     *                  @OA\Property(property="trade_name", type="string", example="Nombre comercial"),
     *                  @OA\Property(property="document_type_id", type="string", example="Tipo de documento"),
     *                  @OA\Property(property="document", type="string", example="Número de documento"),
     *                  @OA\Property(property="location_id", type="string", example="Ubicación"),
     *                  @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                  @OA\Property(property="address", type="string", example="Dirección"),
     *                  @OA\Property(property="neighborhood", type="string", example="Barrio"),
     *                  @OA\Property(property="phone_country_id", type="string", example="País del código de teléfono"),
     *                  @OA\Property(property="phone", type="string", example="Número de teléfono"),
     *                  @OA\Property(property="file", type="string", example="Archivo")
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
    public function store(SupplierStoreRequest $request)
    {
        try {
            $supplier = new Supplier();
            $supplier->code = $request->input('code');
            $supplier->legal_name = $request->input('legal_name');
            $supplier->trade_name = $request->input('trade_name');
            $supplier->document_type_id = $request->integer('document_type_id');
            $supplier->document = $request->input('document');
            $supplier->location_id = $request->integer('location_id');
            $supplier->location_type = $request->input('location_type');
            $supplier->address = $request->input('address');
            $supplier->neighborhood = $request->input('neighborhood');
            $supplier->phone_country_id = $request->integer('phone_country_id');
            $supplier->phone = $request->input('phone');
            $supplier->email = $request->input('email');
            $supplier->save();

            if ($request->hasFile('file')) app(FileService::class)->save($supplier, $request->file('file'), $request->integer('file_type_id'), 'public', 'supplier');

            return $this->successResponse(
                new SupplierResource($supplier),
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
     *     path="/suppliers/update/{id}",
     *     tags={"Suppliers"},
     *     summary="Editar la información de un proveedor en específico",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"code","legal_name","trade_name","document_type_id","document","location_id","location_type","address","neighborhood","phone_country_id","phone"},
     *                 @OA\Property(property="code", type="string", example="00"),
     *                 @OA\Property(property="legal_name", type="string", example="Nombre legal"),
     *                 @OA\Property(property="trade_name", type="string", example="Nombre comercial"),
     *                 @OA\Property(property="document_type_id", type="integer", example=1),
     *                 @OA\Property(property="document", type="string", example="123456789"),
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                 @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *                 @OA\Property(property="neighborhood", type="string", example="Barrio XYZ"),
     *                 @OA\Property(property="phone", type="string", example="3001234567"),
     *                 @OA\Property(property="file_type_id", type="string", example="1"),
     *                 @OA\Property(property="file", type="string", format="binary", nullable=true, description="Archivo (pdf, doc, etc.)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suppliera editada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     type="object",
     *                     ref="#/components/schemas/Supplier"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                  property="attributes",
     *                  type="object",
     *                  @OA\Property(property="code", type="string", example="00"),
     *                  @OA\Property(property="legal_name", type="string", example="Nombre legal"),
     *                  @OA\Property(property="trade_name", type="string", example="Nombre comercial"),
     *                  @OA\Property(property="document_type_id", type="string", example="Tipo de documento"),
     *                  @OA\Property(property="document", type="string", example="Número de documento"),
     *                  @OA\Property(property="location_id", type="string", example="Ubicación"),
     *                  @OA\Property(property="location_type", type="string", example="App\\Models\\City"),
     *                  @OA\Property(property="address", type="string", example="Dirección"),
     *                  @OA\Property(property="neighborhood", type="string", example="Barrio"),
     *                  @OA\Property(property="phone_country_id", type="string", example="País del código de teléfono"),
     *                  @OA\Property(property="phone", type="string", example="Número de teléfono"),
     *                  @OA\Property(property="file", type="string", example="Archivo")
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
    public function update(SupplierUpdateRequest $request, $id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->code = $request->input('code');
            $supplier->legal_name = $request->input('legal_name');
            $supplier->trade_name = $request->input('trade_name');
            $supplier->document_type_id = $request->integer('document_type_id');
            $supplier->document = $request->input('document');
            $supplier->location_id = $request->integer('location_id');
            $supplier->location_type = $request->input('location_type');
            $supplier->address = $request->input('address');
            $supplier->neighborhood = $request->input('neighborhood');
            $supplier->phone_country_id = $request->integer('phone_country_id');
            $supplier->phone = $request->input('phone');
            $supplier->email = $request->input('email');
            $supplier->save();

            if ($request->hasFile('file')) app(FileService::class)->save($supplier, $request->file('file'), $request->integer('file_type_id'), 'public', 'supplier');

            return $this->successResponse(
                new SupplierResource($supplier),
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
     *     path="/suppliers/delete/{id}",
     *     tags={"Suppliers"},
     *     summary="Desactivar un proveedor específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del proveedor",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suppliera desactivada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     type="object",
     *                     ref="#/components/schemas/Supplier"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
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
     *                 @OA\Property(property="id", type="string", example="Identificador del proveedor")
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
    public function delete(SupplierDeleteRequest $request, $id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            return $this->successResponse(
                new SupplierResource($supplier),
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
     *     path="/suppliers/restore/{id}",
     *     tags={"Suppliers"},
     *     summary="Activar un proveedor específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del proveedor",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suppliera activada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="supplier",
     *                     type="object",
     *                     ref="#/components/schemas/Supplier"
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
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
     *                 @OA\Property(property="id", type="string", example="Identificador del proveedor")
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
    public function restore(SupplierRestoreRequest $request, $id)
    {
        try {
            $supplier = Supplier::withTrashed()->findOrFail($id);
            $supplier->restore();

            return $this->successResponse(
                new SupplierResource($supplier),
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

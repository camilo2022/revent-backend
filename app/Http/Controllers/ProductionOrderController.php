<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductionOrder\ProductionOrderAllRequest;
use App\Http\Requests\ProductionOrder\ProductionOrderApprovedRequest;
use App\Http\Requests\ProductionOrder\ProductionOrderCanceledRequest;
use App\Http\Requests\ProductionOrder\ProductionOrderFindRequest;
use App\Http\Requests\ProductionOrder\ProductionOrderStoreRequest;
use App\Http\Requests\ProductionOrder\ProductionOrderUpdateRequest;
use App\Http\Resources\ProductionOrder\ProductionOrderCollection;
use App\Http\Resources\ProductionOrder\ProductionOrderResource;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantity;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="ProductionOrders",
 *     description="Endpoints para gestionar production_orders"
 * )
 *
 * @OA\Schema(
 *     schema="ProductionOrder",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trademark_id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="RV0001"),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="subcategory_id", type="integer", example=1),
 *     @OA\Property(property="cost", type="string", example="85000.00"),
 *     @OA\Property(property="price", type="string", example="150000.00"),
 *     @OA\Property(property="description", type="string", nullable=true, example="descripcion del production_ordero"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(
 *         property="trademark",
 *         type="object",
 *         ref="#/components/schemas/Trademark"
 *     ),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         ref="#/components/schemas/Category"
 *     ),
 *     @OA\Property(
 *         property="subcategory",
 *         type="object",
 *         ref="#/components/schemas/Subcategory"
 *     ),
 *     @OA\Property(
 *         property="colors",
 *         type="array",
 *         description="Detalles de nivel color (ProductionOrderDetail con model_type=ProductionOrder). Cada uno puede traer su propio arreglo 'sizes' con las tallas hijas.",
 *         @OA\Items(ref="#/components/schemas/ProductionOrderDetail")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProductionOrderDetail",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", format="uuid", nullable=true, example="000x0000-x00x-00x0-x000-00000000000"),
 *     @OA\Property(property="model_id", type="integer", example=1),
 *     @OA\Property(property="model_type", type="string", example="App\\Models\\ProductionOrder"),
 *     @OA\Property(property="assignable_id", type="integer", example=1),
 *     @OA\Property(property="assignable_type", type="string", example="App\\Models\\Color"),
 *     @OA\Property(property="description", type="string", nullable=true, example="descripcion del production_ordero"),
 *     @OA\Property(property="code", type="string", example="AGUILA-01-36"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(
 *         property="model",
 *         discriminator=@OA\Discriminator(
 *             propertyName="type",
 *             mapping={
 *                 "production_order"="#/components/schemas/ProductionOrder",
 *                 "production_order_detail"="#/components/schemas/ProductionOrderDetail"
 *             }
 *         ),
 *         oneOf={
 *             @OA\Schema(ref="#/components/schemas/ProductionOrder"),
 *             @OA\Schema(ref="#/components/schemas/ProductionOrderDetail")
 *         }
 *     ),
 *     @OA\Property(
 *         property="assignable",
 *         discriminator=@OA\Discriminator(
 *             propertyName="type",
 *             mapping={
 *                 "color"="#/components/schemas/Color",
 *                 "size"="#/components/schemas/Size"
 *             }
 *         ),
 *         oneOf={
 *             @OA\Schema(ref="#/components/schemas/Color"),
 *             @OA\Schema(ref="#/components/schemas/Size")
 *         }
 *     ),
 *     @OA\Property(
 *         property="sizes",
 *         type="array",
 *         description="Solo presente en nivel color: tallas hijas de este registro",
 *         @OA\Items(ref="#/components/schemas/ProductionOrderDetail")
 *     )
 * )
 */
class ProductionOrderController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/production_orders/all",
     *     tags={"ProductionOrders"},
     *     summary="Listar production_orders",
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
     *         @OA\Schema(type="string", enum={"id","trademark_id","code","category_id","subcategory_id","observation","created_at","updated_at"}, example="code")
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de production_orders cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="production_orders",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/ProductionOrder")
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
    public function all(ProductionOrderAllRequest $request)
    {
        try {
            $production_orders = ProductionOrder::with([
                    'supplier', 'production_order_details' => [
                        'store', 'product_detail' => [
                            'model' => ['trademark', 'category', 'subcategory'],
                            'assignable', 'photo', 'sizes' => ['assignable']
                        ],
                        'production_order_detail_quantities' => ['product_detail' => ['assignable']]
                    ],
                ])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $production_orders = $production_orders->paginate($request->integer('per_page', $production_orders->count()));

            return $this->successResponse(
                new ProductionOrderCollection($production_orders),
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
     *     path="/production_orders/find/{id}",
     *     tags={"ProductionOrders"},
     *     summary="Obtener un production_ordero especifica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del production_ordero",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categoria cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="production_order",
     *                     ref="#/components/schemas/ProductionOrder"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del production_ordero no registrado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación."),
     *             @OA\Property(
     *                 property="attributes",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example=1)
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
    public function find(ProductionOrderFindRequest $request, $id)
    {
        try {
            $production_order = ProductionOrder::with([
                    'supplier', 'production_order_details' => [
                        'store', 'product_detail' => [
                            'model' => ['trademark', 'category', 'subcategory'],
                            'assignable', 'photo', 'sizes' => ['assignable']
                        ],
                        'production_order_detail_quantities' => ['product_detail' => ['assignable']]
                    ],
                ])->findOrFail($id);

            return $this->successResponse(
                new ProductionOrderResource($production_order),
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
     *     path="/production_orders/store",
     *     tags={"ProductionOrders"},
     *     summary="Crear un production_ordero",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"trademark_id","code","category_id","subcategory_id","cost","price"},
     *             @OA\Property(property="trademark_id", type="integer", example=1, description="ID de la marca"),
     *             @OA\Property(property="code", type="string", example="REF-001", description="Código o referencia del production_ordero"),
     *             @OA\Property(property="category_id", type="integer", example=2, description="ID de la categoría"),
     *             @OA\Property(property="subcategory_id", type="integer", example=5, description="ID de la subcategoría"),
     *             @OA\Property(property="observation", type="string", nullable=true, example="ProductionOrdero de temporada", description="Observación adicional del production_ordero"),
     *             @OA\Property(property="cost", type="string", example="85000.00", description="Costo de produccion"),
     *             @OA\Property(property="price", type="string", example="150000.00", description="Precio de venta"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ProductionOrdero creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="production_order", ref="#/components/schemas/ProductionOrder")),
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
     *                 @OA\Property(property="trademark_id", type="string", example="Identificador de la marca"),
     *                 @OA\Property(property="code", type="string", example="Código - Referencia"),
     *                 @OA\Property(property="category_id", type="string", example="Identificador de la categoría"),
     *                 @OA\Property(property="subcategory_id", type="string", example="Identificador de la subcategoría"),
     *                 @OA\Property(property="observation", type="string", example="Observación"),
     *                 @OA\Property(property="cost", type="string", description="Costo de produccion"),
     *                 @OA\Property(property="price", type="string", description="Precio de venta"),
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="trademark_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="code", type="array", @OA\Items(type="string", example="Debe tener 8 caracteres.")),
     *                 @OA\Property(property="category_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="subcategory_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="details.0.color_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="details.0.size_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="details.1", type="array", @OA\Items(type="string", example="La combinación de color y talla ya fue agregada."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse")
     *     )
     * )
     */
    public function store(ProductionOrderStoreRequest $request)
    {
        try {
            $production_order = new ProductionOrder();
            $production_order->consecutive = DB::selectOne('CALL production_order_consecutive()')->consecutive;
            $production_order->due_date = $request->date('due_date', 'Y-m-d');
            $production_order->supplier_id = $request->integer('supplier_id');
            $production_order->vat_percentage = $request->input('vat_percentage', 100);
            $production_order->delivery_note_percentage = $request->input('delivery_note_percentage', 0);
            $production_order->status = ProductionOrder::PENDING;
            $production_order->save();

            foreach ($request->input('production_order_details', []) as $production_order_detail_item) {
                $production_order_detail = new ProductionOrderDetail();
                $production_order_detail->production_order_id = $production_order->id;
                $production_order_detail->product_detail_id = $production_order_detail_item['product_detail_id'];
                $production_order_detail->store_id = $production_order_detail_item['store_id'];
                $production_order_detail->cost = $production_order_detail_item['cost'];
                $production_order_detail->price = $production_order_detail_item['price'];
                $production_order_detail->observation = $production_order_detail_item['observation'] ?? null;
                $production_order_detail->save();

                foreach ($production_order_detail_item['production_order_detail_quantities'] as $production_order_detail_quantity_item) {
                    $production_order_detail_quantity = new ProductionOrderDetailQuantity();
                    $production_order_detail_quantity->production_order_detail_id = $production_order_detail->id;
                    $production_order_detail_quantity->product_detail_id = $production_order_detail_quantity_item['product_detail_id'];
                    $production_order_detail_quantity->quantity = $production_order_detail_quantity_item['quantity'];
                    $production_order_detail_quantity->save();
                }
            }

            return $this->successResponse(
                new ProductionOrderResource($production_order),
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
     *     path="/production_orders/update/{id}",
     *     tags={"ProductionOrders"},
     *     summary="Editar un production_ordero específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del production_ordero",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"trademark_id","code","category_id","subcategory_id","cost","price"},
     *             @OA\Property(property="trademark_id", type="integer", example=1, description="ID de la marca"),
     *             @OA\Property(property="code", type="string", example="REF-001", description="Código o referencia del production_ordero"),
     *             @OA\Property(property="category_id", type="integer", example=2, description="ID de la categoría"),
     *             @OA\Property(property="subcategory_id", type="integer", example=5, description="ID de la subcategoría"),
     *             @OA\Property(property="observation", type="string", nullable=true, example="ProductionOrdero de temporada", description="Observación adicional del production_ordero"),
     *             @OA\Property(property="cost", type="string", example="85000.00", description="Costo de produccion"),
     *             @OA\Property(property="price", type="string", example="150000.00", description="Precio de venta"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ProductionOrdero editado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="production_order", ref="#/components/schemas/ProductionOrder")),
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
     *                 @OA\Property(property="trademark_id", type="string", example="Identificador de la marca"),
     *                 @OA\Property(property="code", type="string", example="Código - Referencia"),
     *                 @OA\Property(property="category_id", type="string", example="Identificador de la categoría"),
     *                 @OA\Property(property="subcategory_id", type="string", example="Identificador de la subcategoría"),
     *                 @OA\Property(property="observation", type="string", example="Observación"),
     *                 @OA\Property(property="cost", type="string", description="Costo de produccion"),
     *                 @OA\Property(property="price", type="string", description="Precio de venta"),
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="trademark_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="code", type="array", @OA\Items(type="string", example="Debe tener 8 caracteres.")),
     *                 @OA\Property(property="category_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="subcategory_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="details.0.color_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="details.0.size_id", type="array", @OA\Items(type="string", example="Es obligatorio.")),
     *                 @OA\Property(property="details.1", type="array", @OA\Items(type="string", example="La combinación de color y talla ya fue agregada."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse")
     *     )
     * )
     */
    public function update(ProductionOrderUpdateRequest $request, $id)
    {
        try {
            $production_order = ProductionOrder::findOrFail($id);
            $production_order->due_date = $request->date('due_date', 'Y-m-d');
            $production_order->supplier_id = $request->integer('supplier_id');
            $production_order->vat_percentage = $request->input('vat_percentage', 100);
            $production_order->delivery_note_percentage = $request->input('delivery_note_percentage', 0);
            $production_order->save();

            foreach ($request->input('production_order_details', []) as $production_order_detail_item) {
                $production_order_detail = ProductionOrderDetail::updateOrCreate(
                    [
                        'production_order_id' => $production_order->id,
                        'product_detail_id' => $production_order_detail_item['product_detail_id'],
                        'store_id' => $production_order_detail_item['store_id'],
                    ],
                    [
                        'cost' => $production_order_detail_item['cost'],
                        'price' => $production_order_detail_item['price'],
                        'observation' => $production_order_detail_item['observation'] ?? null,
                    ]
                );

                foreach ($production_order_detail_item['production_order_detail_quantities'] as $production_order_detail_quantity_item) {
                    ProductionOrderDetailQuantity::updateOrCreate(
                        [
                            'production_order_detail_id' => $production_order_detail->id,
                            'product_detail_id' => $production_order_detail_quantity_item['product_detail_id']
                        ],
                        [
                            'quantity' => $production_order_detail_quantity_item['quantity']
                        ]
                    );
                }
            }

            return $this->successResponse(
                new ProductionOrderResource($production_order),
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

    public function approved(ProductionOrderApprovedRequest $request, $id)
    {

        try {
            $production_order = ProductionOrder::findOrFail($id);
            $production_order->status = ProductionOrder::APPROVED;
            $production_order->save();

            return $this->successResponse(
                new ProductionOrderResource($production_order),
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

    public function canceled(ProductionOrderCanceledRequest $request, $id)
    {

        try {
            $production_order = ProductionOrder::findOrFail($id);
            $production_order->status = ProductionOrder::CANCELED;
            $production_order->save();

            return $this->successResponse(
                new ProductionOrderResource($production_order),
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

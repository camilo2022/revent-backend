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
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="ProductionOrders",
 *     description="Órdenes de producción por referencia: colores, tiendas y cantidades por talla"
 * )
 *
 * @OA\Schema(
 *     schema="ProductionOrderRow",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="consecutive", type="string", example="PO261104001"),
 *     @OA\Property(property="status", type="string", enum={"Pendiente","Aprobado","Cancelado"}, example="Pendiente"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2026-12-01"),
 *     @OA\Property(property="vat_percentage", type="integer", example=100),
 *     @OA\Property(property="delivery_note_percentage", type="integer", example=0),
 *     @OA\Property(property="supplier", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="code", type="string", example="AB"),
 *         @OA\Property(property="legal_name", type="string", example="PROVEEDOR SAS"),
 *         @OA\Property(property="trade_name", type="string", nullable=true, example=null)
 *     ),
 *     @OA\Property(property="technical_sheet", type="object",
 *         @OA\Property(property="id", type="integer", example=4),
 *         @OA\Property(property="status", type="string", example="Aprobado"),
 *         @OA\Property(property="product", type="object",
 *             @OA\Property(property="id", type="integer", example=2),
 *             @OA\Property(property="code", type="string", example="RB265001"),
 *             @OA\Property(property="name", type="string", example="ACTION")
 *         )
 *     ),
 *     @OA\Property(property="colors_count", type="integer", example=2),
 *     @OA\Property(property="stores_count", type="integer", example=30),
 *     @OA\Property(property="total_pairs", type="integer", example=1200),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-04 10:00:00")
 * )
 *
 * @OA\Schema(
 *     schema="ProductionOrder",
 *     type="object",
 *     description="Detalle de la orden: colores, y por color las tiendas con sus tallas",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="consecutive", type="string", example="PO261104001"),
 *     @OA\Property(property="status", type="string", enum={"Pendiente","Aprobado","Cancelado"}, example="Pendiente"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2026-12-01"),
 *     @OA\Property(property="vat_percentage", type="integer", example=100),
 *     @OA\Property(property="delivery_note_percentage", type="integer", example=0),
 *     @OA\Property(property="supplier", type="object"),
 *     @OA\Property(property="technical_sheet", type="object"),
 *     @OA\Property(property="details", type="array", @OA\Items(type="object",
 *         @OA\Property(property="id", type="integer", example=7),
 *         @OA\Property(property="product_detail_id", type="integer", example=31),
 *         @OA\Property(property="code", type="string", example="RB265001-99"),
 *         @OA\Property(property="color", type="object",
 *             @OA\Property(property="id", type="integer", example=3),
 *             @OA\Property(property="name", type="string", example="NEGRO"),
 *             @OA\Property(property="code", type="string", example="99")
 *         ),
 *         @OA\Property(property="cost", type="string", example="85000.00"),
 *         @OA\Property(property="price", type="string", example="189900.00"),
 *         @OA\Property(property="observation", type="string", nullable=true, example=null),
 *         @OA\Property(property="total", type="integer", example=78),
 *         @OA\Property(property="stores", type="array", @OA\Items(type="object",
 *             @OA\Property(property="store", type="object",
 *                 @OA\Property(property="id", type="integer", example=3),
 *                 @OA\Property(property="code", type="string", example="G2"),
 *                 @OA\Property(property="name", type="string", example="ALEGRA")
 *             ),
 *             @OA\Property(property="total", type="integer", example=24),
 *             @OA\Property(property="sizes", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="product_detail_id", type="integer", example=40),
 *                 @OA\Property(property="code", type="string", example="RB265001-35-99"),
 *                 @OA\Property(property="name", type="string", example="35"),
 *                 @OA\Property(property="quantity", type="integer", example=2)
 *             ))
 *         ))
 *     )),
 *     @OA\Property(property="totals", type="object",
 *         @OA\Property(property="pairs", type="integer", example=78),
 *         @OA\Property(property="colors", type="integer", example=1),
 *         @OA\Property(property="stores", type="integer", example=4)
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-04 10:00:00"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-04 10:00:00")
 * )
 */
class ProductionOrderController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    // Relaciones del detalle completo (colores, tiendas y tallas con sus códigos)
    private const WITH = [
        'supplier',
        'technical_sheet.product',
        'production_order_details.product_detail.assignable',
        'production_order_details.production_order_detail_quantities.store',
        'production_order_details.production_order_detail_quantities.product_detail.assignable',
    ];

    /**
     * @OA\Get(
     *     path="/production_orders/all",
     *     tags={"ProductionOrders"},
     *     summary="Listar órdenes de producción",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", description="Filtro de búsqueda (consecutivo, proveedor, producto, estado).", required=false, @OA\Schema(type="string", example="PO2611")),
     *     @OA\Parameter(name="column", in="query", description="Columna a ordenar.", required=false, @OA\Schema(type="string", enum={"id","consecutive","due_date","status","created_at","updated_at"}, example="id")),
     *     @OA\Parameter(name="dir", in="query", description="Orden de datos.", required=false, @OA\Schema(type="string", enum={"asc","desc"}, example="desc")),
     *     @OA\Parameter(name="status", in="query", description="Solo órdenes en un estado.", required=false, @OA\Schema(type="string", enum={"Pendiente","Aprobado","Cancelado"}, example="Pendiente")),
     *     @OA\Parameter(name="supplier_id", in="query", description="Solo órdenes de un proveedor.", required=false, @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Registros por página. Sin enviarlo se devuelven todos.", required=false, @OA\Schema(type="integer", example=10)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de órdenes cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="production_orders", type="array", @OA\Items(ref="#/components/schemas/ProductionOrderRow")),
     *                 @OA\Property(property="meta", type="object", @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="total", type="integer", example=1),
     *                     @OA\Property(property="count", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=1),
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="total_pages", type="integer", example=1)
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function all(ProductionOrderAllRequest $request)
    {
        try {
            $production_orders = ProductionOrder::with(['supplier', 'technical_sheet.product'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('status'), function ($query) use ($request) {
                    return $query->where('status', $request->input('status'));
                })
                ->when($request->filled('supplier_id'), function ($query) use ($request) {
                    return $query->where('supplier_id', $request->integer('supplier_id'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderByDesc('id');
                });

            $production_orders = $production_orders->paginate($request->integer('per_page', $production_orders->count()));

            $this->addTotals($production_orders->getCollection());

            return $this->successResponse(
                new ProductionOrderCollection($production_orders),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/production_orders/find/{id}",
     *     tags={"ProductionOrders"},
     *     summary="Obtener una orden de producción con sus colores, tiendas y tallas",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la orden de producción", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Orden cargada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="production_order", ref="#/components/schemas/ProductionOrder")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Identificador no registrado"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function find(ProductionOrderFindRequest $request, $id)
    {
        try {
            $production_order = ProductionOrder::with(self::WITH)->findOrFail($id);

            return $this->successResponse(
                new ProductionOrderResource($production_order),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/production_orders/store",
     *     tags={"ProductionOrders"},
     *     summary="Crear una orden de producción (queda en estado Pendiente)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"technical_sheet_id","supplier_id","due_date","vat_percentage","delivery_note_percentage","production_order_details"},
     *             @OA\Property(property="technical_sheet_id", type="integer", example=4),
     *             @OA\Property(property="supplier_id", type="integer", example=1),
     *             @OA\Property(property="due_date", type="string", format="date", example="2026-12-01"),
     *             @OA\Property(property="vat_percentage", type="integer", example=100),
     *             @OA\Property(property="delivery_note_percentage", type="integer", example=0),
     *             @OA\Property(property="production_order_details", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="product_detail_id", type="integer", description="Color del producto", example=31),
     *                 @OA\Property(property="cost", type="number", example=85000),
     *                 @OA\Property(property="price", type="number", example=189900),
     *                 @OA\Property(property="observation", type="string", nullable=true, example=null),
     *                 @OA\Property(property="production_order_detail_quantities", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="store_id", type="integer", example=3),
     *                     @OA\Property(property="product_detail_id", type="integer", description="Talla del color", example=40),
     *                     @OA\Property(property="quantity", type="integer", example=2)
     *                 ))
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Orden creada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="production_order", ref="#/components/schemas/ProductionOrder")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación (ficha no aprobada, color o talla que no pertenecen a la ficha, repetidos, etc.)"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function store(ProductionOrderStoreRequest $request)
    {
        try {
            $production_order = DB::transaction(function () use ($request) {
                $production_order = new ProductionOrder();
                // El consecutivo se pide dentro de la transacción para que no se repita
                $production_order->consecutive = DB::selectOne('CALL production_order_consecutive()')->consecutive;
                $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
                $production_order->supplier_id = $request->integer('supplier_id');
                $production_order->due_date = $request->date('due_date', 'Y-m-d');
                $production_order->vat_percentage = $request->integer('vat_percentage');
                $production_order->delivery_note_percentage = $request->integer('delivery_note_percentage');
                $production_order->status = ProductionOrder::PENDING;
                $production_order->save();

                $this->syncDetails($production_order, $request->input('production_order_details'));

                return $production_order;
            });

            return $this->successResponse(
                new ProductionOrderResource($production_order->load(self::WITH)),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/production_orders/update/{id}",
     *     tags={"ProductionOrders"},
     *     summary="Actualizar una orden pendiente: lo que no se envía se elimina",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la orden de producción", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"technical_sheet_id","supplier_id","due_date","vat_percentage","delivery_note_percentage","production_order_details"},
     *             @OA\Property(property="technical_sheet_id", type="integer", example=4),
     *             @OA\Property(property="supplier_id", type="integer", example=1),
     *             @OA\Property(property="due_date", type="string", format="date", example="2026-12-01"),
     *             @OA\Property(property="vat_percentage", type="integer", example=100),
     *             @OA\Property(property="delivery_note_percentage", type="integer", example=0),
     *             @OA\Property(property="production_order_details", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="product_detail_id", type="integer", description="Color del producto", example=31),
     *                 @OA\Property(property="cost", type="number", example=85000),
     *                 @OA\Property(property="price", type="number", example=189900),
     *                 @OA\Property(property="observation", type="string", nullable=true, example=null),
     *                 @OA\Property(property="production_order_detail_quantities", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="store_id", type="integer", example=3),
     *                     @OA\Property(property="product_detail_id", type="integer", description="Talla del color", example=40),
     *                     @OA\Property(property="quantity", type="integer", example=2)
     *                 ))
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orden actualizada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="production_order", ref="#/components/schemas/ProductionOrder")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación (la orden ya no está pendiente, etc.)"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function update(ProductionOrderUpdateRequest $request, $id)
    {
        try {
            $production_order = DB::transaction(function () use ($request, $id) {
                $production_order = ProductionOrder::findOrFail($id);
                $production_order->technical_sheet_id = $request->integer('technical_sheet_id');
                $production_order->supplier_id = $request->integer('supplier_id');
                $production_order->due_date = $request->date('due_date', 'Y-m-d');
                $production_order->vat_percentage = $request->integer('vat_percentage');
                $production_order->delivery_note_percentage = $request->integer('delivery_note_percentage');
                $production_order->save();

                $this->syncDetails($production_order, $request->input('production_order_details'));

                return $production_order;
            });

            return $this->successResponse(
                new ProductionOrderResource($production_order->load(self::WITH)),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Patch(
     *     path="/production_orders/approved/{id}",
     *     tags={"ProductionOrders"},
     *     summary="Aprobar la orden (Pendiente → Aprobado)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la orden de producción", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Orden aprobada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="production_order", ref="#/components/schemas/ProductionOrder")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="La orden no está pendiente"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function approved(ProductionOrderApprovedRequest $request, $id)
    {
        return $this->changeStatus($id, ProductionOrder::APPROVED);
    }

    /**
     * @OA\Patch(
     *     path="/production_orders/canceled/{id}",
     *     tags={"ProductionOrders"},
     *     summary="Cancelar la orden (Pendiente o Aprobado → Cancelado)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador de la orden de producción", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Orden cancelada",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="production_order", ref="#/components/schemas/ProductionOrder")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="La orden ya está cancelada"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function canceled(ProductionOrderCanceledRequest $request, $id)
    {
        return $this->changeStatus($id, ProductionOrder::CANCELED);
    }

    private function changeStatus($id, string $status)
    {
        try {
            $production_order = DB::transaction(function () use ($id, $status) {
                $production_order = ProductionOrder::findOrFail($id);
                $production_order->status = $status;
                $production_order->save();

                return $production_order;
            });

            return $this->successResponse(
                new ProductionOrderResource($production_order->load(self::WITH)),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    // Deja la orden con los colores y cantidades enviados: crea, actualiza y elimina lo que ya no viene
    private function syncDetails(ProductionOrder $production_order, array $details): void
    {
        $keep = [];

        foreach ($details as $item) {
            $detail = ProductionOrderDetail::updateOrCreate(
                ['production_order_id' => $production_order->id, 'product_detail_id' => $item['product_detail_id']],
                ['cost' => $item['cost'], 'price' => $item['price'], 'observation' => $item['observation'] ?? null]
            );

            $keep[] = $detail->id;
            $this->syncQuantities($detail, $item['production_order_detail_quantities']);
        }

        // Colores quitados de la orden (sus cantidades se borran en cascada)
        $production_order->production_order_details()->whereNotIn('id', $keep)->get()->each->delete();
    }

    // Cantidades de un color por tienda y talla; las de cero no se guardan
    private function syncQuantities(ProductionOrderDetail $detail, array $quantities): void
    {
        $existing = $detail->production_order_detail_quantities()->get()->keyBy(fn ($row) => $row->store_id . '-' . $row->product_detail_id);
        $keep = [];

        foreach ($quantities as $item) {
            if ((int) $item['quantity'] === 0) continue;

            $key = $item['store_id'] . '-' . $item['product_detail_id'];
            $keep[] = $key;

            if ($row = $existing->get($key)) {
                $row->update(['quantity' => $item['quantity']]);
            } else {
                ProductionOrderDetailQuantity::create([
                    'production_order_detail_id' => $detail->id,
                    'store_id' => $item['store_id'],
                    'product_detail_id' => $item['product_detail_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        }

        // Cantidades que ya no vienen (la llave es tienda-talla)
        $existing->reject(fn ($row, $key) => in_array($key, $keep))->each->delete();
    }

    // Pares, colores y tiendas de cada orden del listado, en una sola consulta
    private function addTotals($orders): void
    {
        if ($orders->isEmpty()) return;

        $totals = DB::table('production_order_details as d')
            ->leftJoin('production_order_detail_quantities as q', 'q.production_order_detail_id', '=', 'd.id')
            ->whereIn('d.production_order_id', $orders->pluck('id'))
            ->groupBy('d.production_order_id')
            ->selectRaw('d.production_order_id, count(distinct d.id) as colors, count(distinct q.store_id) as stores, coalesce(sum(q.quantity), 0) as pairs')
            ->get()
            ->keyBy('production_order_id');

        foreach ($orders as $order) {
            $row = $totals->get($order->id);
            $order->setAttribute('colors_count', $row->colors ?? 0);
            $order->setAttribute('stores_count', $row->stores ?? 0);
            $order->setAttribute('total_pairs', $row->pairs ?? 0);
        }
    }
}

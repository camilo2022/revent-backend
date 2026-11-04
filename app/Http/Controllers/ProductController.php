<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\ProductAllRequest;
use App\Http\Requests\Product\ProductFindRequest;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Size;
use App\Services\ProductCodeService;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Productos (referencias): código generado, nombre, colores y tallas"
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="RB265001"),
 *     @OA\Property(property="name", type="string", example="ACTION"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Botín clásico"),
 *     @OA\Property(property="cost", type="string", example="85000.00"),
 *     @OA\Property(property="price", type="string", example="189900.00"),
 *     @OA\Property(property="collection_id", type="integer", example=1),
 *     @OA\Property(property="collection", type="object", nullable=true, ref="#/components/schemas/Collection"),
 *     @OA\Property(property="technical_sheet", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="status", type="string", example="Aprobado")
 *     ),
 *     @OA\Property(property="colors", type="array", description="Colores del producto; cada uno con sus tallas",
 *         @OA\Items(type="object",
 *             @OA\Property(property="id", type="integer", example=31),
 *             @OA\Property(property="code", type="string", example="RB265001-99"),
 *             @OA\Property(property="description", type="string", nullable=true, example=null),
 *             @OA\Property(property="color", type="object"),
 *             @OA\Property(property="sizes", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="id", type="integer", example=40),
 *                 @OA\Property(property="code", type="string", example="RB265001-35-99"),
 *                 @OA\Property(property="description", type="string", nullable=true, example=null),
 *                 @OA\Property(property="size", type="object")
 *             ))
 *         )
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-04 10:00:00"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-04 10:00:00")
 * )
 */
class ProductController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    // Relaciones que necesita el recurso para armar colores, tallas y sus códigos
    private const WITH = ['collection', 'technical_sheet', 'colors.assignable', 'colors.sizes.assignable'];

    /**
     * @OA\Get(
     *     path="/products/all",
     *     tags={"Products"},
     *     summary="Listar productos",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", description="Filtro de búsqueda (código, nombre, descripción, colección).", required=false, @OA\Schema(type="string", example="ACTION")),
     *     @OA\Parameter(name="column", in="query", description="Columna a ordenar.", required=false, @OA\Schema(type="string", enum={"id","code","name","price","cost","created_at","updated_at"}, example="code")),
     *     @OA\Parameter(name="dir", in="query", description="Orden de datos.", required=false, @OA\Schema(type="string", enum={"asc","desc"}, example="asc")),
     *     @OA\Parameter(name="collection_id", in="query", description="Solo productos de una colección.", required=false, @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Registros por página. Sin enviarlo se devuelven todos.", required=false, @OA\Schema(type="integer", example=10)),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de productos cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="products", type="array", @OA\Items(ref="#/components/schemas/Product")),
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
    public function all(ProductAllRequest $request)
    {
        try {
            $products = Product::with(self::WITH)
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('collection_id'), function ($query) use ($request) {
                    return $query->where('collection_id', $request->integer('collection_id'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderBy('code');
                });

            $products = $products->paginate($request->integer('per_page', $products->count()));

            return $this->successResponse(
                new ProductCollection($products),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/products/find/{id}",
     *     tags={"Products"},
     *     summary="Obtener un producto con sus colores y tallas",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador del producto", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Producto cargado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="product", ref="#/components/schemas/Product")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Identificador no registrado"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function find(ProductFindRequest $request, $id)
    {
        try {
            $product = Product::with(self::WITH)->findOrFail($id);

            return $this->successResponse(
                new ProductResource($product),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/products/store",
     *     tags={"Products"},
     *     summary="Crear un producto (el código lo genera el servidor)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"collection_id","name","price","colors_id"},
     *             @OA\Property(property="collection_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="ACTION"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Botín clásico"),
     *             @OA\Property(property="price", type="number", example=189900),
     *             @OA\Property(property="cost", type="number", nullable=true, example=85000),
     *             @OA\Property(property="colors_id", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="color_id", type="integer", example=3),
     *                 @OA\Property(property="description", type="string", nullable=true, example=null),
     *                 @OA\Property(property="sizes_id", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="size_id", type="integer", example=10),
     *                     @OA\Property(property="description", type="string", nullable=true, example=null)
     *                 ))
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Producto creado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="product", ref="#/components/schemas/Product")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function store(ProductStoreRequest $request)
    {
        try {
            $product = DB::transaction(function () use ($request) {
                $product = new Product();
                // El código se asigna dentro de la transacción para que el consecutivo no se repita
                $product->code = app(ProductCodeService::class)->next($request->integer('collection_id'));
                $product->name = $request->input('name');
                $product->collection_id = $request->integer('collection_id');
                $product->description = $request->input('description');
                $product->cost = $request->float('cost');
                $product->price = $request->float('price');
                $product->save();

                $this->syncColors($product, $request->input('colors_id'));

                return $product;
            });

            return $this->successResponse(
                new ProductResource($product->load(self::WITH)),
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/products/update/{id}",
     *     tags={"Products"},
     *     summary="Actualizar un producto y sincronizar sus colores y tallas",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", description="Identificador del producto", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","colors_id"},
     *             @OA\Property(property="name", type="string", example="ACTION"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Botín clásico"),
     *             @OA\Property(property="price", type="number", example=189900),
     *             @OA\Property(property="cost", type="number", nullable=true, example=85000),
     *             @OA\Property(property="colors_id", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="color_id", type="integer", example=3),
     *                 @OA\Property(property="description", type="string", nullable=true, example=null),
     *                 @OA\Property(property="sizes_id", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="size_id", type="integer", example=10),
     *                     @OA\Property(property="description", type="string", nullable=true, example=null)
     *                 ))
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto actualizado con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="product", ref="#/components/schemas/Product")),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación (por ejemplo, quitar un color o talla usado en una orden)"),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
     *     @OA\Response(response=500, description="Error del servidor", @OA\JsonContent(ref="#/components/schemas/ServerErrorResponse"))
     * )
     */
    public function update(ProductUpdateRequest $request, $id)
    {
        try {
            $product = DB::transaction(function () use ($request, $id) {
                $product = Product::findOrFail($id);
                $product->name = $request->input('name');
                $product->description = $request->input('description');
                $product->price = $request->float('price');

                // Sin costo en la petición se conserva el actual
                if ($request->filled('cost')) {
                    $product->cost = $request->float('cost');
                }

                $product->save();

                $this->syncColors($product, $request->input('colors_id'));

                return $product;
            });

            return $this->successResponse(
                new ProductResource($product->load(self::WITH)),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    // Deja el producto con los colores y tallas enviados: crea los nuevos, actualiza los existentes y quita los demás
    private function syncColors(Product $product, array $colors): void
    {
        $keepColors = [];

        foreach ($colors as $item) {
            $color = ProductDetail::updateOrCreate(
                ['model_type' => Product::class, 'model_id' => $product->id, 'assignable_type' => Color::class, 'assignable_id' => $item['color_id']],
                ['description' => $item['description'] ?? null]
            );

            $keepColors[] = $color->id;
            $keepSizes = [];

            foreach ($item['sizes_id'] as $sizeItem) {
                $size = ProductDetail::updateOrCreate(
                    ['model_type' => ProductDetail::class, 'model_id' => $color->id, 'assignable_type' => Size::class, 'assignable_id' => $sizeItem['size_id']],
                    ['description' => $sizeItem['description'] ?? null]
                );

                $keepSizes[] = $size->id;
            }

            // Tallas que ya no están en este color
            ProductDetail::where('model_type', ProductDetail::class)->where('model_id', $color->id)->whereNotIn('id', $keepSizes)->get()->each->delete();
        }

        // Colores quitados, con todas sus tallas
        $removed = ProductDetail::where('model_type', Product::class)->where('model_id', $product->id)->whereNotIn('id', $keepColors)->get();

        foreach ($removed as $color) {
            ProductDetail::where('model_type', ProductDetail::class)->where('model_id', $color->id)->get()->each->delete();
            $color->delete();
        }
    }
}

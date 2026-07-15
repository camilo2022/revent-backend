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
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Endpoints para gestionar products"
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trademark_id", type="integer", example=1),
 *     @OA\Property(property="code", type="string", example="RV0001"),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="subcategory_id", type="integer", example=1),
 *     @OA\Property(property="description", type="string", nullable=true, example="descripcion del producto"),
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
 *         description="Detalles de nivel color (ProductDetail con model_type=Product). Cada uno puede traer su propio arreglo 'sizes' con las tallas hijas.",
 *         @OA\Items(ref="#/components/schemas/ProductDetail")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ProductDetail",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", format="uuid", nullable=true, example="000x0000-x00x-00x0-x000-00000000000"),
 *     @OA\Property(property="model_id", type="integer", example=1),
 *     @OA\Property(property="model_type", type="string", example="App\\Models\\Product"),
 *     @OA\Property(property="assignable_id", type="integer", example=1),
 *     @OA\Property(property="assignable_type", type="string", example="App\\Models\\Color"),
 *     @OA\Property(property="description", type="string", nullable=true, example="descripcion del producto"),
 *     @OA\Property(property="code", type="string", example="AGUILA-01-36"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-03-30T13:17:29.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-04-06T15:21:16.000000Z"),
 *     @OA\Property(
 *         property="model",
 *         discriminator=@OA\Discriminator(
 *             propertyName="type",
 *             mapping={
 *                 "product"="#/components/schemas/Product",
 *                 "product_detail"="#/components/schemas/ProductDetail"
 *             }
 *         ),
 *         oneOf={
 *             @OA\Schema(ref="#/components/schemas/Product"),
 *             @OA\Schema(ref="#/components/schemas/ProductDetail")
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
 *         @OA\Items(ref="#/components/schemas/ProductDetail")
 *     )
 * )
 */
class ProductController extends Controller
{
    use ApiMessage, ApiResponser;

    /**
     * @OA\Get(
     *     path="/products/all",
     *     tags={"Products"},
     *     summary="Listar products",
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
     *         @OA\Schema(type="string", enum={"id","trademark_id","code","category_id","subcategory_id","observation","created_at", "updated_at"}, example="code")
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
     *         description="Lista de products cargada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Product")
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
    public function all(ProductAllRequest $request)
    {
        try {
            $products = Product::with(['product_details'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $products = $products->paginate($request->integer('per_page', $products->count()));

            return $this->successResponse(
                new ProductCollection($products),
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
     *     path="/products/find/{id}",
     *     tags={"Products"},
     *     summary="Obtener un producto especifica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del producto",
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
     *                     property="product",
     *                     ref="#/components/schemas/Product"
     *                 ),
     *             ),
     *             @OA\Property(property="message", type="string", example="Operación completada con éxito."),
     *             @OA\Property(property="error", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Identificador del producto no registrado.",
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
    public function find(ProductFindRequest $request, $id)
    {
        try {
            $product = Product::with(['product_details'])->findOrFail($id);

            return $this->successResponse(
                new ProductResource($product),
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
     *     path="/products/store",
     *     tags={"Products"},
     *     summary="Crear un producto",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"trademark_id","code","category_id","subcategory_id"},
     *             @OA\Property(property="trademark_id", type="integer", example=1, description="ID de la marca"),
     *             @OA\Property(property="code", type="string", example="REF-001", description="Código o referencia del producto"),
     *             @OA\Property(property="category_id", type="integer", example=2, description="ID de la categoría"),
     *             @OA\Property(property="subcategory_id", type="integer", example=5, description="ID de la subcategoría"),
     *             @OA\Property(property="observation", type="string", nullable=true, example="Producto de temporada", description="Observación adicional del producto")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="product", ref="#/components/schemas/Product")),
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
     *                 @OA\Property(property="observation", type="string", example="Observación")
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
    public function store(ProductStoreRequest $request)
    {
        try {
            $product = new Product();
            $product->trademark_id = $request->integer('trademark_id');
            $product->code = $request->input('code');
            $product->category_id = $request->integer('category_id');
            $product->subcategory_id = $request->integer('subcategory_id');
            $product->description = $request->input('description');
            $product->save();

            foreach ($request->input('colors_id', []) as $color_item) {
                $product_color = new ProductDetail();
                $product_color->model_id = $product->id;
                $product_color->model_type = Product::class;
                $product_color->assignable_id = $color_item['color_id'];
                $product_color->assignable_type = Color::class;
                $product_color->description = $color_item['description'] ?? null;
                $product_color->save();

                foreach ($color_item['sizes_id'] as $size_item) {
                    $product_size = new ProductDetail();
                    $product_size->model_id = $product_color->id;
                    $product_size->model_type = ProductDetail::class;
                    $product_size->assignable_id = $size_item['size_id'];
                    $product_size->assignable_type = Size::class;
                    $product_size->description = $size_item['description'] ?? null;
                    $product_size->save();
                }
            }

            return $this->successResponse(
                new ProductResource($product),
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
     *     path="/products/update/{id}",
     *     tags={"Products"},
     *     summary="Editar un producto específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Identificador del producto",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"trademark_id","code","category_id","subcategory_id"},
     *             @OA\Property(property="trademark_id", type="integer", example=1, description="ID de la marca"),
     *             @OA\Property(property="code", type="string", example="REF-001", description="Código o referencia del producto"),
     *             @OA\Property(property="category_id", type="integer", example=2, description="ID de la categoría"),
     *             @OA\Property(property="subcategory_id", type="integer", example=5, description="ID de la subcategoría"),
     *             @OA\Property(property="observation", type="string", nullable=true, example="Producto de temporada", description="Observación adicional del producto")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto editado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", @OA\Property(property="product", ref="#/components/schemas/Product")),
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
     *                 @OA\Property(property="observation", type="string", example="Observación")
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
    public function update(ProductUpdateRequest $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->trademark_id = $request->integer('trademark_id');
            $product->code = $request->input('code');
            $product->category_id = $request->integer('category_id');
            $product->subcategory_id = $request->integer('subcategory_id');
            $product->observation = $request->input('observation');
            $product->save();

            foreach ($request->input('colors_id', []) as $color_item) {
                $product_color = new ProductDetail();
                $product_color->model_id = $product->id;
                $product_color->model_type = Product::class;
                $product_color->assignable_id = $color_item['color_id'];
                $product_color->assignable_type = Color::class;
                $product_color->description = $color_item['description'] ?? null;
                $product_color->save();

                foreach ($color_item['sizes_id'] as $size_item) {
                    $product_size = new ProductDetail();
                    $product_size->model_id = $product_color->id;
                    $product_size->model_type = ProductDetail::class;
                    $product_size->assignable_id = $size_item['size_id'];
                    $product_size->assignable_type = Size::class;
                    $product_size->description = $size_item['description'] ?? null;
                    $product_size->save();
                }
            }

            return $this->successResponse(
                new ProductResource($product),
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

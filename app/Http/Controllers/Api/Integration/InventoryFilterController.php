<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\InventoryFilter\AllWarehousesRequest;
use App\Http\Requests\Api\Integration\InventoryFilter\CatalogsRequest;
use App\Http\Requests\Api\Integration\InventoryFilter\ImagesRequest;
use App\Http\Requests\Api\Integration\InventoryFilter\SearchRequest;
use App\Http\Resources\Api\Integration\InventoryFilter\InventoryCatalogsResource;
use App\Http\Resources\Api\Integration\InventoryFilter\InventoryImagesResource;
use App\Http\Resources\Api\Integration\InventoryFilter\InventoryStockResource;
use App\Services\Integration\InventoryFilterService;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Support\Integration\WarehouseAccess;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;

/**
 * Filtro de inventario. Cada consulta va a Siigo como en Integration (sin caché nuevo); el usuario solo ve
 * las bodegas de sus tiendas, salvo quien tiene acceso a todas.
 */
class InventoryFilterController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    public function __construct(
        private InventoryFilterService $inventory,
        private SiigoClient $client,
        private SiigoCatalogService $catalog
    ) {
    }

    public function catalogs(CatalogsRequest $request)
    {
        try {
            $warehouses = $this->catalog->warehousesWithTransit($this->client->token());
            $warehouses = WarehouseAccess::filter(collect($warehouses)->all(), Auth::user());

            return $this->successResponse(
                new InventoryCatalogsResource(['warehouses' => $warehouses, 'color_groups' => $this->inventory->colorGroups()]),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function search(SearchRequest $request)
    {
        try {
            $products = $this->inventory->warehouseStock((int) $request->input('warehouse_id'));

            return $this->successResponse(new InventoryStockResource(['productos' => $products]), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function allWarehouses(AllWarehousesRequest $request)
    {
        try {
            $payload = $this->inventory->allWarehouses($request->input('referencia'));
            $payload = $this->inventory->onlyAccessible($payload, Auth::user());

            return $this->successResponse(new InventoryStockResource($payload), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function images(ImagesRequest $request)
    {
        try {
            $page = $this->inventory->imagesPage(
                $request->input('referencia'),
                (int) ($request->input('page') ?? 1),
                (int) ($request->input('per_page') ?? 10)
            );

            return $this->successResponse(new InventoryImagesResource($page), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

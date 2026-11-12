<?php

namespace App\Services\Integration;

use App\Models\User;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoDocumentService;
use App\Services\SiigoPurchaseOrderCacheService;
use App\Support\Integration\LegacyCacheKeys;

/**
 * Busca una orden de compra de Siigo para recibirla.
 *
 * Copiado de Integration\InvoicePurchaseOrderSiigoController::invoice_purchase_order_search (commit b8694f73).
 * Si cambia ese método en el controlador legacy, hay que replicarlo aquí.
 *
 * Diferencias con el legacy: el token del usuario ya lo validó el FormRequest (un token inválido es un 422, nunca un
 * 401), y cada ítem trae `Accessible` según las tiendas asignadas al usuario del ERP. La orden trae `accessible`
 * (true solo si todas sus bodegas son accesibles).
 */
class PurchaseOrderReceptionService
{
    public function __construct(
        private SiigoClient $client,
        private SiigoCatalogService $catalog,
        private SiigoDocumentService $siigoDocuments,
        private SiigoPurchaseOrderCacheService $purchaseCache,
        private PurchaseOrderItemsParser $items
    ) {
    }

    /** La orden de compra con sus ítems, o null si no existe. */
    public function find(string $query, User $user): ?array
    {
        $token = $this->client->token();

        $data = $this->siigoDocuments->quicksearch($token, $query, 19);

        if ($data === null) {
            return null;
        }

        $purchaseOrder = collect($data)->first(fn ($item) => data_get($item, 'codeEntity') === $query);

        if (!$purchaseOrder) {
            return null;
        }

        preg_match('/ERPDocumentID=(\d+)/', (string) data_get($purchaseOrder, 'urlEntity'), $matches);

        $acEntryId = $matches[1] ?? null;

        if (!$acEntryId) {
            return null;
        }

        $cacheKey = LegacyCacheKeys::purchaseDetail('OC', $acEntryId);

        $warehouses = $this->catalog->warehousesById($token);
        $order = $this->purchaseCache->fetchDetail($token, (int) $acEntryId, $cacheKey);
        $order['Items'] = $this->items->parse($order, $warehouses);

        return $this->withAccess($order, $user);
    }

    /** Marca cada ítem y la orden según el acceso del usuario a la bodega de cada ítem. */
    public function withAccess(array $order, User $user): array
    {
        $allowAll = $user->hasAccessToAllStores();

        $order['Items'] = array_map(function (array $item) use ($user, $allowAll) {
            $code = $item['WarehouseCode'] ?? null;

            $item['Accessible'] = $allowAll || (is_numeric($code) && $user->canAccessWarehouse((int) $code));

            return $item;
        }, $order['Items'] ?? []);

        $order['accessible'] = $allowAll || collect($order['Items'])->every(fn ($item) => $item['Accessible']);

        return $order;
    }
}

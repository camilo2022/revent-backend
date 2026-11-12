<?php

namespace App\Models\Concerns;

use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Tiendas (bodegas de Siigo) a las que un modelo tiene acceso, de forma polimórfica como los roles y permisos.
 * Quien tenga el permiso `stores.access_all` (supervisor) opera en todas las bodegas.
 */
trait HasStores
{
    public function stores(): MorphToMany
    {
        return $this->morphToMany(Store::class, 'model', 'model_has_stores', 'model_id', 'store_id');
    }

    public function hasAccessToAllStores(): bool
    {
        return $this->hasPermissionTo('stores.access_all');
    }

    /** Ids de bodega y de tránsito de Siigo a los que tiene acceso, o ['*'] si accede a todas. */
    public function accessibleWarehouseIds(): array
    {
        if ($this->hasAccessToAllStores()) {
            return ['*'];
        }

        return $this->stores()
            ->get(['siigo_warehouse_id', 'siigo_transit_warehouse_id'])
            ->flatMap(fn ($store) => [$store->siigo_warehouse_id, $store->siigo_transit_warehouse_id])
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function canAccessWarehouse(int $siigoWarehouseId): bool
    {
        $ids = $this->accessibleWarehouseIds();

        return $ids === ['*'] || in_array($siigoWarehouseId, $ids, true);
    }
}

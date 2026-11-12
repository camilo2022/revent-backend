<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

/** Tiendas (bodegas) asignadas a un usuario y si tiene acceso a todas. */
class UserStoresResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'user_id' => $this->id,
            'access_all' => $this->hasAccessToAllStores(),
            'stores' => $this->stores->map(fn ($store) => [
                'id' => $store->id,
                'code' => $store->code,
                'name' => $store->name,
                'siigo_warehouse_id' => $store->siigo_warehouse_id,
                'siigo_transit_warehouse_id' => $store->siigo_transit_warehouse_id,
            ])->values(),
        ];
    }
}

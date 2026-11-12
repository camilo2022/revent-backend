<?php

namespace App\Support\Integration;

use App\Models\User;

/**
 * Filtra bodegas de Siigo por las tiendas asignadas a un usuario (más sus bodegas de tránsito).
 * Las bodegas sin tienda mapeada solo las ve quien tiene acceso a todas.
 */
final class WarehouseAccess
{
    /**
     * @param array<int, array<string, mixed>> $warehouses elementos con la clave $key (id de bodega de Siigo)
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $warehouses, User $user, string $key = 'id'): array
    {
        $ids = $user->accessibleWarehouseIds();

        if ($ids === ['*']) {
            return array_values($warehouses);
        }

        return array_values(array_filter(
            $warehouses,
            fn ($warehouse) => in_array((int) ($warehouse[$key] ?? 0), $ids, true)
        ));
    }

    public static function allows(User $user, int $siigoWarehouseId): bool
    {
        return $user->canAccessWarehouse($siigoWarehouseId);
    }
}

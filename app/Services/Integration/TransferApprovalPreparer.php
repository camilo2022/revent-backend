<?php

namespace App\Services\Integration;

use App\Models\Store;
use App\Models\User;
use App\Services\Siigo\SiigoTransferApprovalService;

/**
 * Prepara la aprobación de una nota de traslado (NT) hacia una bodega de tránsito para la API.
 *
 * Copiado de Integration\TransferApprovalSiigoController::prepare (commit b8694f73).
 * Si cambia ese método privado en el controlador legacy, hay que replicarlo aquí.
 * El correo del resultado ya no sale de la lista del job de web sino de la tienda (stores.email).
 *
 * Diferencia con el legacy: el acceso a la bodega de tránsito ya no se valida con la lista de ids de usuarios
 * de Siigo (BODEGAS[...]['users']) sino con las tiendas asignadas al usuario autenticado del ERP.
 * El token de Siigo solo se usa para consultar lo disponible y para el job que crea el traslado.
 */
class TransferApprovalPreparer
{
    public function __construct(private SiigoTransferApprovalService $approval)
    {
    }

    /**
     * Lee la NT, comprueba el acceso a la bodega de tránsito y calcula lo máximo a aprobar por producto
     * (menor entre lo que dice la NT y lo disponible hoy en tránsito).
     *
     * @throws \DomainException si la NT no existe o el usuario no tiene la tienda asignada
     */
    public function prepare(User $user, string $siigoToken, string $query): array
    {
        $found = $this->approval->find($query);

        if (!$found['note']) {
            throw new \DomainException($found['error']);
        }

        $note = $found['note'];

        if (!$user->canAccessWarehouse((int) $note['transit']['id'])) {
            throw new \DomainException("No tienes acceso a la bodega {$note['transit']['name']}. Pide que te asignen la tienda.");
        }

        $available = $this->approval->availability($siigoToken, array_column($note['items'], 'code'), (int) $note['transit']['id']);

        $note['items'] = array_map(function ($item) use ($available) {
            $item['available'] = $available[$item['code']] ?? 0;
            $item['max'] = min($item['requested'], $item['available']);

            return $item;
        }, $note['items']);

        return $note;
    }

    /** Correo de la tienda dueña de la bodega de tránsito (stores.email); si no tiene, operaciones. */
    public function warehouseEmail(int|string $warehouseId): string
    {
        return Store::where('siigo_transit_warehouse_id', (int) $warehouseId)->value('email')
            ?: config('siigo.mail.operations');
    }
}

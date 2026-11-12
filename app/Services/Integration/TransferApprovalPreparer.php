<?php

namespace App\Services\Integration;

use App\Models\User;
use App\Services\Siigo\SiigoTransferApprovalService;

/**
 * Prepara la aprobación de una nota de traslado (NT) hacia una bodega de tránsito para la API.
 *
 * Copiado de Integration\TransferApprovalSiigoController::prepare y ::warehouseEmail (commit b8694f73).
 * Si cambia alguno de esos métodos privados en el controlador legacy, hay que replicarlo aquí.
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

    /**
     * Correo asociado a la bodega de tránsito (el job recibe uno solo), completado con el dominio igual que en el job.
     * Prefiere el del dominio de la empresa; si la bodega no tiene correos, operaciones.
     */
    public function warehouseEmail(int|string $warehouseId): string
    {
        $domain = config('siigo.mail.domain');

        $emails = array_map(
            fn (string $email) => str_contains($email, '@') ? $email : "{$email}@{$domain}",
            $this->approval->warehouses()['TRANSITO'][$warehouseId]['emails'] ?? []
        );

        return collect($emails)->first(fn (string $email) => str_ends_with($email, "@{$domain}"))
            ?? $emails[0]
            ?? config('siigo.mail.operations');
    }
}

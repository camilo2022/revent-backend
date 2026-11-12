<?php

namespace App\Support\Integration;

use App\Services\SiigoPurchaseOrderCacheService;

/**
 * Claves de caché y bloqueos que la API comparte con las pantallas de Integration (mismo store, misma tabla).
 * Son copias exactas de las del legacy: NO se renombran ni se cambian sus TTL sin cambiar también el legacy.
 * Los datos que ya manejan sus propios servicios (SiigoProductsCache, SyncUnicoRunLog, rutas NT, fechas de
 * traslado, contactos de clientes) se usan a través de esas clases, no por su clave.
 */
final class LegacyCacheKeys
{
    /** Bloqueo anti doble clic de la sincronización manual. Integration\SyncUnicoSiigoController (60 s). */
    public const SYNC_UNICO_LOCK_SECONDS = 60;

    /** Bloqueo por nota de traslado confirmada. Integration\TransferApprovalSiigoController (120 s). */
    public const TRANSFER_APPROVAL_LOCK_SECONDS = 120;

    /** Stock de una referencia en todas las bodegas. Integration\InventoryFilterSiigoController (60 s). */
    public const INVENTORY_ALL_WAREHOUSES_SECONDS = 60;

    /** Fotos de una referencia. Integration\InventoryFilterSiigoController (5 min). */
    public const INVENTORY_IMAGES_SECONDS = 300;

    public static function syncUnicoManualLock(): string
    {
        return 'SIIGO:SYNC-UNICO:MANUAL-LOCK';
    }

    public static function transferApprovalLock(string $noteCode): string
    {
        return "transfer-approval:{$noteCode}";
    }

    public static function inventoryAllWarehouses(string $reference): string
    {
        return 'INVENTORY_FILTER:ALL_WAREHOUSES:v3:' . $reference;
    }

    public static function inventoryImages(string $reference): string
    {
        return 'INVENTORY_FILTER:IMAGES:' . mb_strtoupper($reference);
    }

    /** Listado de facturas (FC) u órdenes de compra (OC) de un día. */
    public static function purchaseDay(string $type, string $day): string
    {
        return SiigoPurchaseOrderCacheService::dayKey($type, $day);
    }

    /** Detalle de un documento: OC-ID-{id} / FC-ID-{id}. */
    public static function purchaseDetail(string $type, int|string $id): string
    {
        return "{$type}-ID-{$id}";
    }

    /** Proveedor de cuentas por pagar: el legacy usa el UUID (MsThirdPartyID) tal cual como clave. */
    public static function provider(string $uuid): string
    {
        return $uuid;
    }
}

<?php

namespace Tests\Feature\Integration;

use App\Support\Integration\LegacyCacheKeys;
use Tests\TestCase;

/**
 * Fija los textos de las claves y bloqueos que la API comparte con Integration: si alguien los "mejora"
 * sin cambiar también el legacy, la API y las pantallas dejarían de compartir caché y bloqueos.
 */
class LegacyCacheKeysTest extends TestCase
{
    public function test_las_claves_legacy_no_cambian(): void
    {
        $this->assertSame('SIIGO:SYNC-UNICO:MANUAL-LOCK', LegacyCacheKeys::syncUnicoManualLock());
        $this->assertSame('transfer-approval:NT-1-123', LegacyCacheKeys::transferApprovalLock('NT-1-123'));
        $this->assertSame('INVENTORY_FILTER:ALL_WAREHOUSES:v3:REF123', LegacyCacheKeys::inventoryAllWarehouses('REF123'));
        $this->assertSame('INVENTORY_FILTER:IMAGES:REF123', LegacyCacheKeys::inventoryImages('ref123'));
        $this->assertSame('SIIGO-FC-DAY-2026-11-10', LegacyCacheKeys::purchaseDay('FC', '2026-11-10'));
        $this->assertSame('OC-ID-5', LegacyCacheKeys::purchaseDetail('OC', 5));
        $this->assertSame('FC-ID-77', LegacyCacheKeys::purchaseDetail('FC', '77'));
        $this->assertSame('b5d1d3f2-0000-0000-0000-000000000001', LegacyCacheKeys::provider('b5d1d3f2-0000-0000-0000-000000000001'));
    }

    public function test_los_tiempos_legacy_no_cambian(): void
    {
        $this->assertSame(60, LegacyCacheKeys::SYNC_UNICO_LOCK_SECONDS);
        $this->assertSame(120, LegacyCacheKeys::TRANSFER_APPROVAL_LOCK_SECONDS);
        $this->assertSame(60, LegacyCacheKeys::INVENTORY_ALL_WAREHOUSES_SECONDS);
        $this->assertSame(300, LegacyCacheKeys::INVENTORY_IMAGES_SECONDS);
    }
}

<?php

namespace App\Support\Integration;

use App\Jobs\ImportMasiveTransferSiigoJob;
use App\Models\Store;
use App\Models\User;

/**
 * Correos con copia de la recepción de una orden de compra: el correo de la tienda que recibe, el mismo al que
 * llegan sus traslados. La tienda se identifica por el usuario de Siigo del token (lista `users` de cada bodega
 * en ImportMasiveTransferSiigoJob::BODEGAS, que no se modifica) o, en la API, por las tiendas del usuario del ERP.
 * El correo sale de la tienda (`stores.email`); si ahí está vacío, del que ya usan los traslados.
 * Nunca falla: si algo sale mal, la recepción se envía igual, solo sin copia.
 */
class ReceptionMailCopy
{
    /** Un usuario que está en más bodegas que esto (administradores) no es de una tienda: no lleva copia. */
    private const MAX_WAREHOUSES = 3;

    /**
     * Copia según las bodegas de la recepción (web): el correo que tiene cada código en BODEGAS, sin base de datos.
     * Si el código es de una bodega con tránsito, también va el correo de su tránsito, y si es de un tránsito, el de
     * la bodega a la que pasa (p. ej. TRANSITO REVENT 68 <-> REVENT 15), porque la recepción pasa de una a la otra.
     */
    public static function forWarehouses(array $codes, array $exclude = []): array
    {
        try {
            $bodegas = self::bodegas();
            $emails = [];

            foreach (array_unique(array_map('intval', $codes)) as $code) {
                $emails = array_merge($emails, self::legacyEmails($code), self::legacyEmails($code, 'TRANSITO'));

                // Bodega con tránsito asociado y, al revés, tránsito que alimenta una bodega
                if ($transit = $bodegas['DIRECTO'][$code]['transito'] ?? null) {
                    $emails = array_merge($emails, self::legacyEmails((int) $transit, 'TRANSITO'));
                }

                foreach ($bodegas['DIRECTO'] as $directCode => $bodega) {
                    if (($bodega['transito'] ?? null) === $code) {
                        $emails = array_merge($emails, self::legacyEmails((int) $directCode));
                    }
                }
            }

            return self::clean($emails, $exclude);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Bodega de destino de la orden que se recepciona: es una sola y la misma en todos sus ítems (`WarehouseCode`).
     * Si los ítems no la traen, se intenta con el encabezado ("3 - ALEGRA").
     */
    public static function warehouseCodes(array $reception): array
    {
        $codes = collect($reception['Items'] ?? [])->pluck('WarehouseCode')->filter(fn ($code) => is_numeric($code));

        if ($codes->isEmpty() && preg_match('/^\s*(-?\d+)\s*-/', (string) data_get($reception, 'Order.Warehouse'), $m)) {
            $codes->push($m[1]);
        }

        return $codes->map(fn ($code) => (int) $code)->unique()->values()->all();
    }

    /**
     * Copia según el usuario de Siigo del token (la web). Solo usa el array BODEGAS del job de traslados, sin base de
     * datos; $fromStores = true (solo la API) da prioridad al correo guardado en `stores.email`.
     */
    public static function forSiigoUser(int $siigoUserId, array $exclude = [], bool $fromStores = false): array
    {
        try {
            return self::clean(self::fromWarehouses(self::warehousesOf($siigoUserId), $fromStores), $exclude);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /** Copia según las tiendas del usuario del ERP (la API); si no tiene, según su usuario de Siigo. */
    public static function forUser(User $user, int $siigoUserId, array $exclude = []): array
    {
        try {
            if (!$user->hasAccessToAllStores()) {
                $emails = $user->stores()->whereNotNull('stores.email')->pluck('stores.email')->all();

                if ($emails) {
                    return self::clean($emails, $exclude);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return self::forSiigoUser($siigoUserId, $exclude, true);
    }

    /** Códigos de las bodegas de tienda donde está el usuario de Siigo. */
    private static function warehousesOf(int $siigoUserId): array
    {
        $codes = collect(self::bodegas()['DIRECTO'])
            ->filter(fn (array $b, $code) => (int) $code > 0 && in_array($siigoUserId, $b['users'] ?? [], true))
            ->keys()
            ->all();

        return count($codes) > self::MAX_WAREHOUSES ? [] : $codes;
    }

    private static function fromWarehouses(array $codes, bool $fromStores): array
    {
        if (!$codes) {
            return [];
        }

        $stored = $fromStores ? Store::whereIn('siigo_warehouse_id', $codes)->whereNotNull('email')->pluck('email', 'siigo_warehouse_id') : collect();
        $emails = [];

        foreach ($codes as $code) {
            $emails = array_merge($emails, isset($stored[$code]) ? [$stored[$code]] : self::legacyEmails($code));
        }

        return $emails;
    }

    private static function legacyEmails(int $code, string $type = 'DIRECTO'): array
    {
        $domain = config('siigo.mail.domain');

        return array_map(
            fn (string $email) => str_contains($email, '@') ? $email : "{$email}@{$domain}",
            self::bodegas()[$type][$code]['emails'] ?? []
        );
    }

    private static function bodegas(): array
    {
        return (new \ReflectionClassConstant(ImportMasiveTransferSiigoJob::class, 'BODEGAS'))->getValue();
    }

    /** Sin repetidos, sin correos inválidos y sin los que ya van como destinatario. */
    private static function clean(array $emails, array $exclude): array
    {
        $exclude = array_map('strtolower', $exclude);

        return array_values(array_unique(array_filter(
            array_map('trim', $emails),
            fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array(strtolower($email), $exclude, true)
        )));
    }
}

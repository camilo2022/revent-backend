<?php

namespace App\Services;

use App\Models\Collection;

/**
 * Códigos de producto: prefijo + año (2) + colección (1) + consecutivo (3), reinicia por colección.
 * Ejemplo: RB265001. Debe llamarse dentro de una transacción para que el bloqueo evite números repetidos.
 */
class ProductCodeService
{
    private const MAX_NUMBER = 999;

    public function next(int $collectionId): string
    {
        // El contador se mueve con cada producto: no se audita
        return Collection::withoutAuditing(function () use ($collectionId) {
            // Bloquea la colección mientras se asigna el número
            $collection = Collection::whereKey($collectionId)->lockForUpdate()->firstOrFail();
            $number = $collection->last_number + 1;

            if ($number > self::MAX_NUMBER) {
                throw new \DomainException("La colección {$collection->name} ya llegó al límite de " . self::MAX_NUMBER . ' productos.');
            }

            $collection->settings = (object) array_merge((array) $collection->settings, ['last_number' => $number]);
            $collection->save();

            return $collection->product_prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
        });
    }
}

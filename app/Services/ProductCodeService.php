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
        // Bloquea la colección mientras se asigna el número
        $collection = Collection::whereKey($collectionId)->lockForUpdate()->firstOrFail();

        if ($collection->last_number >= self::MAX_NUMBER) {
            throw new \DomainException("La colección {$collection->name} ya llegó al límite de " . self::MAX_NUMBER . ' productos.');
        }

        $collection->last_number++;
        $collection->save();

        return $collection->product_prefix . str_pad($collection->last_number, 3, '0', STR_PAD_LEFT);
    }
}

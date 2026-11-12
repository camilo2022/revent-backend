<?php

namespace App\Http\Resources\Api\Integration\InventoryFilter;

use Illuminate\Http\Resources\Json\JsonResource;

/** Productos con su stock por color y talla (de una bodega o de todas), con sus errores de consulta si los hubo. */
class InventoryStockResource extends JsonResource
{
    public static function map($stock): array
    {
        return [
            'referencia' => $stock['referencia'] ?? null,
            'productos' => $stock['productos'] ?? [],
            'errores' => $stock['errores'] ?? [],
        ];
    }

    public function toArray($request)
    {
        return ['inventory' => self::map($this->resource)];
    }
}

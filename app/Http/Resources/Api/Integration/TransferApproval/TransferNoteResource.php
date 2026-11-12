<?php

namespace App\Http\Resources\Api\Integration\TransferApproval;

use Illuminate\Http\Resources\Json\JsonResource;

/** Nota de traslado lista para aprobar: bodegas, productos con lo pedido, lo disponible y lo máximo a aprobar. */
class TransferNoteResource extends JsonResource
{
    public function toArray($request)
    {
        return ['note' => self::map($this->resource)];
    }

    public static function map(array $note): array
    {
        return [
            'code' => $note['code'],
            'id' => $note['id'],
            'date' => $note['date'],
            'origin' => $note['origin'],
            'transit' => $note['transit'],
            'destination' => $note['destination'],
            'items' => $note['items'],
            'images' => $note['images'] ?? [],
        ];
    }
}

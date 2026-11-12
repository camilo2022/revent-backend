<?php

namespace App\Http\Resources\Api\Integration\ProductTraceability;

use Illuminate\Http\Resources\Json\JsonResource;

/** Trazabilidad con saldo acumulado y auditoría de tránsitos de una bodega: productos con sus movimientos y el resumen. */
class TraceabilityAuditResource extends JsonResource
{
    public static function map(array $audit): array
    {
        return [
            'warehouse' => $audit['warehouse'],
            'transit' => $audit['transit'],
            'start_date' => $audit['start_date'],
            'products' => $audit['products'],
            'summary' => $audit['summary'],
        ];
    }

    public function toArray($request)
    {
        return ['audit' => self::map($this->resource)];
    }
}

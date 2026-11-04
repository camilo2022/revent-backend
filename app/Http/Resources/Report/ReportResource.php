<?php

namespace App\Http\Resources\Report;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /** Definición pública del reporte: nombre, grupos de campos y filtros (sin el modelo interno). */
    public static function map($report): array
    {
        return [
            'id' => $report->id,
            'name' => $report->name,
            'description' => $report->description,
            'fields' => $report->settings->fields ?? [],
            'filters' => $report->settings->filters ?? new \stdClass(),
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at,
            'deleted_at' => $report->deleted_at,
        ];
    }
}

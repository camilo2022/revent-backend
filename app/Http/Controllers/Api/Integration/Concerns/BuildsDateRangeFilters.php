<?php

namespace App\Http\Controllers\Api\Integration\Concerns;

use Carbon\Carbon;

/**
 * Filtros de fecha de los exportes por correo: el rango manual tiene prioridad sobre el mes (por defecto, el mes en curso).
 * Copiado de Integration\Concerns\ExportsToEmail::dateRangeFilters.
 */
trait BuildsDateRangeFilters
{
    protected function dateRangeFilters(array $validated): array
    {
        if (!empty($validated['created_start']) && !empty($validated['created_end'])) {
            $createdStart = Carbon::parse($validated['created_start'])->format('Y-m-d H:i:s');
            $createdEnd = Carbon::parse($validated['created_end'])->format('Y-m-d H:i:s');
        } else {
            $month = $validated['month'] ?? null;
            $date = $month ? Carbon::createFromFormat('Y-m', $month) : Carbon::now();

            $createdStart = $date->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s');
            $createdEnd = $date->copy()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');
        }

        return [
            'created_start' => $createdStart,
            'created_end' => $createdEnd,
            'page_size' => $validated['page_size'] ?? 100,
        ];
    }
}

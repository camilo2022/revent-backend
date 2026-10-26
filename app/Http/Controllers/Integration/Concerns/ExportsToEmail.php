<?php

namespace App\Http\Controllers\Integration\Concerns;

use App\Http\Requests\Integration\Export\ExportRequest;
use Carbon\Carbon;

/**
 * Piezas comunes de los controladores que despachan un exporte de Siigo por correo:
 * filtros de fecha y despacho. La validación vive en Requests\Integration\Export\ExportRequest.
 */
trait ExportsToEmail
{
    /** Filtros de fecha: el rango manual tiene prioridad sobre el mes (por defecto, el mes en curso). */
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

    /** Despacha el job con el rango de fechas validado y devuelve la vista de descarga. */
    protected function dispatchDateRangeExport(ExportRequest $request, string $job, string $view)
    {
        $emails = $request->input('emails', config('siigo.mail.exports'));

        $job::dispatch($this->dateRangeFilters($request->validated()), $emails);

        return view($view, compact('emails'));
    }
}

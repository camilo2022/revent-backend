<?php

namespace App\Http\Resources\Api\Integration\SyncUnico;

use Illuminate\Http\Resources\Json\JsonResource;

/** Historial corto de las sincronizaciones con Único y el correo al que llega el resultado. */
class SyncUnicoRunCollection extends JsonResource
{
    public static function map(array $run): array
    {
        return [
            'run_id' => $run['run_id'] ?? null,
            'source' => $run['source'] ?? null,
            'status' => $run['status'] ?? null,
            'started_at' => $run['started_at'] ?? null,
            'finished_at' => $run['finished_at'] ?? null,
            'range' => $run['range'] ?? null,
            'invoices' => $run['invoices'] ?? null,
            'sent' => $run['sent'] ?? null,
            'inserted' => $run['inserted'] ?? null,
            'rejected' => $run['rejected'] ?? null,
            'error' => $run['error'] ?? null,
        ];
    }

    public function toArray($request)
    {
        return [
            'runs' => collect($this->resource['runs'])->map(fn ($run) => self::map((array) $run))->values()->all(),
            'notify_email' => $this->resource['notify_email'],
        ];
    }
}

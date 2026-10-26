<?php

namespace App\Services\Siigo;

use Illuminate\Http\Client\Response;

/** Reportes de Siigo (getreport) que devuelven una tabla de filas. */
class SiigoReportService
{
    public function __construct(private SiigoClient $client)
    {
    }

    /**
     * Ejecuta un reporte y devuelve data.Value.Table.
     *
     * @param string $errorPrefix texto de la excepción si Siigo responde con error (se le agrega el cuerpo)
     */
    public function table(array $body, string $errorPrefix, ?string $token = null): ?array
    {
        $response = $this->client->report($token ?? $this->client->token(), $body);

        if (!$response->successful()) {
            throw new \Exception($errorPrefix . $response->body());
        }

        return $response->json('data.Value.Table');
    }

    /** URL absoluta del reporte (para peticiones en paralelo con Http::pool). */
    public function url(): string
    {
        return $this->client->url('services', config('siigo.endpoints.services.report'));
    }
}

<?php

namespace App\Jobs\Concerns;

use App\Services\Siigo\SiigoClient;

/** Catálogos y consultas de la API de Siigo compartidos por los jobs de exporte. */
trait LoadsSiigoCatalogs
{
    protected function siigoClient(): SiigoClient
    {
        return app(SiigoClient::class);
    }

    /** Vendedores (usuarios de Siigo) indexados por id. */
    protected function loadSellers(string $token, array $filters = []): array
    {
        $query = ['page_size' => $filters['page_size'] ?? 100];

        if (isset($filters['page'])) {
            $query['page'] = $filters['page'];
        }

        $sellers = $this->siigoClient()->paginate(config('siigo.endpoints.api.users'), $query, $token);

        return collect($sellers)->keyBy('id')->all();
    }

    /** Centros de costo indexados por id. */
    protected function loadCostCenters(string $token): array
    {
        $response = $this->siigoClient()->api($token)->get(config('siigo.endpoints.api.cost_centers'));

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return collect($response->json() ?? [])->keyBy('id')->all();
    }

    /** Recorre un listado paginado (facturas, notas crédito, compras) con los filtros de fecha. */
    protected function loadListing(string $endpoint, string $token, array $filters = []): array
    {
        $allowedFilters = ['page', 'page_size', 'created_start', 'created_end'];

        $query = array_filter(
            array_intersect_key($filters, array_flip($allowedFilters)),
            fn ($value) => $value !== null && $value !== ''
        );

        return $this->siigoClient()->paginate($endpoint, $query, $token);
    }

    /** Tiendas (id de centro de costo => nombre y código). */
    protected function stores(): array
    {
        return [
            3 => ['name' => 'ALEGRA', 'code' => 'G2'],
            4 => ['name' => 'PUNTO DE VENTA', 'code' => 'P7'],
            9 => ['name' => 'MAYALES', 'code' => 'M3'],
            15 => ['name' => 'REVENT', 'code' => 'R1'],
            17 => ['name' => 'OCEAN MALL', 'code' => 'P2'],
            19 => ['name' => 'NUESTRO', 'code' => 'P1'],
            22 => ['name' => 'ALAMEDAS', 'code' => 'P3'],
            24 => ['name' => 'PORTAL', 'code' => 'M2'],
            28 => ['name' => 'PLAZA DEL SOL', 'code' => 'M8'],
            31 => ['name' => 'WEB', 'code' => 'G1'],
            32 => ['name' => 'CASTELLANA', 'code' => 'G4'],
            33 => ['name' => 'UNICO BQ', 'code' => 'G3'],
            37 => ['name' => 'CARNAVAL', 'code' => 'P4'],
            46 => ['name' => 'GUATAPURI', 'code' => 'M7'],
            49 => ['name' => 'VENTURA PLAZA', 'code' => 'M6'],
            50 => ['name' => 'FABRICATO', 'code' => 'M5'],
            51 => ['name' => 'UNICO CALI', 'code' => 'M1'],
            56 => ['name' => 'CARIBE PLAZA 2', 'code' => 'G5'],
            57 => ['name' => 'MAYORCA', 'code' => 'M4'],
            58 => ['name' => 'GRAN MANZANA', 'code' => 'I3'],
            59 => ['name' => 'NUESTRO ATLANTICO', 'code' => 'P6'],
        ];
    }
}

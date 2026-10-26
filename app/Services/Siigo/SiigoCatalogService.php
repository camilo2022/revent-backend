<?php

namespace App\Services\Siigo;

use Illuminate\Support\Collection;

/**
 * Catálogos de la API pública de Siigo que usan varias pantallas:
 * bodegas, usuarios/vendedores.
 */
class SiigoCatalogService
{
    public function __construct(private SiigoClient $client)
    {
    }

    /** Bodegas tal como las entrega Siigo. */
    public function warehouses(?string $token = null): array
    {
        $response = $this->client->api($token)->get(config('siigo.endpoints.api.warehouses'));

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    /** Bodegas indexadas por id, con la opción -1 "SIN ASIGNAR". */
    public function warehousesById(?string $token = null): Collection
    {
        return collect($this->warehouses($token))
            ->keyBy('id')
            ->put(-1, [
                'id' => -1,
                'name' => 'SIN ASIGNAR',
            ]);
    }

    /**
     * Bodegas para filtros: sin las de tránsito, marcando cuáles tienen bodega
     * "TRANSITO ..." asociada, y con "SIN ASIGNAR" de primera.
     */
    public function warehousesWithTransit(?string $token = null): array
    {
        $items = collect($this->warehouses($token))->map(fn ($item) => (array) $item);

        $nombresConTransito = $items
            ->filter(fn ($item) => str_starts_with(trim($item['name']), 'TRANSITO '))
            ->map(fn ($item) => trim(str_replace('TRANSITO ', '', $item['name'])))
            ->map(fn ($nombre) => mb_strtoupper(trim($nombre)))
            ->unique()
            ->values();

        $result = $items
            ->filter(fn ($item) => !str_starts_with(trim($item['name']), 'TRANSITO '))
            ->map(function ($item) use ($nombresConTransito) {
                $item['transito'] = $nombresConTransito->contains(mb_strtoupper(trim($item['name'])));

                return $item;
            })
            ->values()
            ->toArray();

        array_unshift($result, [
            'id' => -1,
            'name' => 'SIN ASIGNAR',
            'transito' => false,
        ]);

        return $result;
    }

    /** Todos los usuarios de Siigo (paginado), indexados por id. */
    public function usersById(?string $token = null): Collection
    {
        return collect($this->client->paginate(config('siigo.endpoints.api.users'), [], $token))->keyBy('id');
    }

    /** Correos de todos los usuarios de Siigo. */
    public function userEmails(?string $token = null): array
    {
        return collect($this->client->paginate(config('siigo.endpoints.api.users'), [], $token))
            ->pluck('email')
            ->toArray();
    }
}

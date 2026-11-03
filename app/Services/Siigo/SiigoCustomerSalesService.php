<?php

namespace App\Services\Siigo;

use Carbon\Carbon;

/**
 * Clientes que compraron en un rango de fechas (y opcionalmente en un centro de costo):
 * facturas de venta de la API pública + nombre y teléfono desde el catálogo de clientes.
 */
class SiigoCustomerSalesService
{
    /** Documento del "cliente genérico" de las ventas de mostrador: no tiene nombre ni teléfono propios. */
    private const GENERIC_IDENTIFICATION = '222222222222';

    public function __construct(private SiigoClient $client)
    {
    }

    /** Centros de costo (id, código y nombre) para el filtro, ordenados por nombre. */
    public function costCenters(string $token): array
    {
        $response = $this->client->api($token)->get(config('siigo.endpoints.api.cost_centers'));

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return collect($response->json() ?? [])->sortBy('name')->values()->all();
    }

    /**
     * @return array<int,array{identification:string,name:string,phone:string,invoices:array}> un registro por cliente
     */
    public function search(string $token, string $dateStart, string $dateEnd, ?int $costCenter): array
    {
        // Siigo toma date_end como límite que no incluye: se suma un día para incluir el último
        $invoices = $this->client->paginate(config('siigo.endpoints.api.invoices'), [
            'date_start' => $dateStart,
            'date_end' => Carbon::parse($dateEnd)->addDay()->format('Y-m-d'),
        ], $token);

        $excluded = config('siigo.invoices.excluded_product_codes');
        $byCustomer = [];

        foreach ($invoices as $invoice) {
            if ($costCenter && (int) ($invoice['cost_center'] ?? 0) !== $costCenter) {
                continue;
            }

            $identification = (string) ($invoice['customer']['identification'] ?? '');

            $items = collect($invoice['items'] ?? [])
                ->whereNotIn('code', $excluded)
                ->map(fn ($item) => [
                    'code' => $item['code'] ?? '',
                    'description' => $item['description'] ?? '',
                    'quantity' => (float) ($item['quantity'] ?? 0),
                ])
                ->values()
                ->all();

            $byCustomer[$identification]['identification'] = $identification;
            $byCustomer[$identification]['invoices'][] = [
                'name' => $invoice['name'] ?? '',
                'date' => $invoice['date'] ?? '',
                'total' => (float) ($invoice['total'] ?? 0),
                'items' => $items,
            ];
        }

        // Una consulta de nombre y teléfono por cliente
        foreach ($byCustomer as $identification => &$customer) {
            $customer += $this->customerContact($token, $identification);
        }

        return collect($byCustomer)->sortBy('name')->values()->all();
    }

    /** @return array{name:string,phone:string} */
    private function customerContact(string $token, string $identification): array
    {
        if ($identification === '' || $identification === self::GENERIC_IDENTIFICATION) {
            return ['name' => 'CLIENTE GENÉRICO (SIN DATOS)', 'phone' => ''];
        }

        $response = $this->client->api($token)->get(config('siigo.endpoints.api.customers'), ['identification' => $identification]);

        // Si Siigo limita las consultas se espera un segundo y se reintenta una vez
        if ($response->status() === 429) {
            sleep(1);
            $response = $this->client->api($token)->get(config('siigo.endpoints.api.customers'), ['identification' => $identification]);
        }

        $customer = $response->successful() ? ($response->json('results.0') ?? []) : [];

        return [
            'name' => mb_strtoupper(trim(implode(' ', $customer['name'] ?? [])) ?: 'SIN NOMBRE'),
            'phone' => collect($customer['phones'] ?? [])->pluck('number')->filter()->implode(' / '),
        ];
    }
}

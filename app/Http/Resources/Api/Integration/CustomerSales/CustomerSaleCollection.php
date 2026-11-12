<?php

namespace App\Http\Resources\Api\Integration\CustomerSales;

use Illuminate\Http\Resources\Json\JsonResource;

/** Clientes que compraron en la fecha, con sus datos de contacto y las facturas con lo que compraron. */
class CustomerSaleCollection extends JsonResource
{
    public static function map($customer): array
    {
        return [
            'identification' => $customer['identification'] ?? '',
            'name' => $customer['name'] ?? '',
            'email' => $customer['email'] ?? '',
            'phone' => $customer['phone'] ?? '',
            'phone_valid' => (bool) ($customer['phone_valid'] ?? false),
            'invoices' => collect($customer['invoices'] ?? [])->map(fn ($invoice) => [
                'name' => $invoice['name'] ?? '',
                'date' => $invoice['date'] ?? '',
                'total' => (float) ($invoice['total'] ?? 0),
                'items' => collect($invoice['items'] ?? [])->map(fn ($item) => [
                    'code' => $item['code'] ?? '',
                    'description' => $item['description'] ?? '',
                    'quantity' => (float) ($item['quantity'] ?? 0),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    public function toArray($request)
    {
        return ['customers' => collect($this->resource)->map(fn ($customer) => self::map($customer))->values()->all()];
    }
}

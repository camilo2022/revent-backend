<?php

namespace App\Services\Integration;

use App\Exports\MasivePurchaseOrderSiigoMultiSheetExport;
use App\Services\Siigo\SiigoClient;

/**
 * Arma el Excel de formato de órdenes de compra masivas: lee de Siigo el tipo de orden, los impuestos, los centros
 * de costo y las bodegas, y los pone en las hojas del formato.
 *
 * Copiado de Integration\MasivePurchaseOrderSiigoController: masive_purchase_order (tipos),
 * masive_purchase_order_format, purchase_order_type, list_taxes, cost_centers y warehouses (commit b8694f73).
 * Si cambia alguno de esos métodos privados en el controlador legacy, hay que replicarlo aquí.
 */
class MasivePurchaseOrderFormatService
{
    /** Tipos de orden de compra de Siigo que se pueden cargar de forma masiva (id => nombre). */
    public const TYPES = [
        23200 => 'OC-1-Orden de compra principal',
        27279 => 'OC-2-Orden de servicio',
        28029 => 'OC-3-Orden Compra Internet',
        30480 => 'OC-4-O.C. Materia Prima',
    ];

    public function __construct(private SiigoClient $siigo)
    {
    }

    /** @return array<int, array{id: int, name: string}> */
    public function types(): array
    {
        $types = [];

        foreach (self::TYPES as $id => $name) {
            $types[] = ['id' => $id, 'name' => $name];
        }

        return $types;
    }

    /** El Excel de formato y el nombre con el que se descarga (sin extensión). */
    public function build(string $token, string $cookie, string|int $purchaseOrderTypeId): array
    {
        $type_details = [
            '0' => 'Producto',
            '1' => 'Activo fijo',
            '2' => 'Gasto / Cuenta contable'
        ];

        $purchase_order_type = $this->purchaseOrderType($token, $cookie, $purchaseOrderTypeId);

        if (!is_object($purchase_order_type)) {
            throw new \DomainException('Siigo no devolvió el tipo de orden de compra. Verifica la cookie de Siigo y vuelve a intentarlo.');
        }

        $list_taxes = $this->listTaxes($token, $cookie);
        $cost_centers = $purchase_order_type->UseCostCenter ? $this->costCenters() : [];
        $warehouses = $this->warehouses();

        $filters = [
            'use_cost_center' => $purchase_order_type->UseCostCenter,
            'is_rete_iva' => $purchase_order_type->IsReteIva,
            'is_rete_ica' => $purchase_order_type->IsReteIca
        ];

        return [
            new MasivePurchaseOrderSiigoMultiSheetExport($purchase_order_type, $filters, $warehouses, $cost_centers, $list_taxes, $type_details),
            "formato_{$purchase_order_type->ERPDocClass}_{$purchase_order_type->ERPDocCode}",
        ];
    }

    private function purchaseOrderType(string $token, string $cookie, string|int $purchase_order_type_id)
    {
        $response = $this->siigo->monolith($token, $cookie)
            ->asMultipart()
            ->post(config('siigo.endpoints.monolith.erp_doc_handler'), [
                ['name' => 'Process', 'contents' => '3'],
                ['name' => 'ERPDocumentTypeID', 'contents' => $purchase_order_type_id],
                ['name' => 'ERPDocType', 'contents' => '0'],
                ['name' => 'ERPDocumentID', 'contents' => ''],
            ]);

        return json_decode((string) $response->json('ERPConfigItem'));
    }

    private function listTaxes(string $token, string $cookie): array
    {
        $response = $this->siigo->monolith($token, $cookie)
            ->asMultipart()
            ->post(config('siigo.endpoints.monolith.erp_doc_handler'), [
                ['name' => 'Process', 'contents' => '2'],
                ['name' => 'ERPDocType', 'contents' => '0'],
                ['name' => 'IsExpense', 'contents' => '1'],
                ['name' => 'ERPDocumentID', 'contents' => '-1'],
            ]);

        $data = $response->json();

        $impCargo = json_decode($data['lstTaxAdd'], true);
        $impRetencion = json_decode($data['lstTaxDisc'], true);

        $noAplica = ['Id' => -1, 'Name' => 'No aplica', 'Value' => -1, 'Type' => -1, 'SubType' => -1];

        $impCargo = array_merge([$noAplica], array_filter($impCargo, fn ($item) => $item['Id'] != -1));

        $impRetencion = array_merge([$noAplica], array_filter($impRetencion, fn ($item) => $item['Id'] != -1));

        return [
            'imp_cargo' => $impCargo,
            'imp_retencion' => $impRetencion,
            'rete_iva' => json_decode($data['lstTaxRetIVA'], true),
            'rete_ica' => json_decode($data['lstTaxRetICA'], true),
        ];
    }

    private function costCenters(): array
    {
        $response = $this->siigo->api()->get(config('siigo.endpoints.api.cost_centers'));

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    private function warehouses(): array
    {
        $response = $this->siigo->api()->get(config('siigo.endpoints.api.warehouses'));

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = collect($response->json())
            ->reject(fn ($item) => str_starts_with(strtoupper($item['name']), 'TRANSITO'))
            ->values()
            ->all();

        return [['id' => '-1', 'name' => 'SIN ASIGNAR'], ...$data];
    }
}

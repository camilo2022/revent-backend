<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasivePurchaseOrderSiigoMultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $filters;

    protected $sheet_purchase_order;
    protected $sheet_purchase_order_details;
    protected $sheet_warehouses;
    protected $sheet_cost_centers;
    protected $sheet_rete_iva;
    protected $sheet_rete_ica;
    protected $sheet_type_details;
    protected $sheet_imp_cargo;
    protected $sheet_imp_retencion;

    public function __construct($purchase_order_type, $filters, $warehouses, $cost_centers, $list_taxes, $type_details)
    {
        $this->filters = $filters;

        $this->sheet_purchase_order = new MasivePurchaseOrderSiigoExport($purchase_order_type, $filters, $cost_centers, $list_taxes['rete_iva'], $list_taxes['rete_ica']);
        $this->sheet_purchase_order_details = new MasivePurchaseOrderDetailSiigoExport($type_details, $warehouses, $list_taxes['imp_cargo'], $list_taxes['imp_retencion']);
        $this->sheet_warehouses = new MasivePurchaseOrderWarehousesSiigoExport($warehouses);

        if($this->filters['use_cost_center']) $this->sheet_cost_centers = new MasivePurchaseOrderCostCenterSiigoExport($cost_centers);
        if($this->filters['is_rete_iva']) $this->sheet_rete_iva = new MasivePurchaseOrderReteIVASiigoExport($list_taxes['rete_iva']);
        if($this->filters['is_rete_ica']) $this->sheet_rete_ica = new MasivePurchaseOrderReteICASiigoExport($list_taxes['rete_ica']);

        $this->sheet_type_details = new MasivePurchaseOrderTypeDetailSiigoExport($type_details);
        $this->sheet_imp_cargo = new MasivePurchaseOrderImpCargoSiigoExport($list_taxes['imp_cargo']);
        $this->sheet_imp_retencion = new MasivePurchaseOrderImpRetencionSiigoExport($list_taxes['imp_retencion']);
    }

    public function sheets(): array
    {
        $sheets = [
            $this->sheet_purchase_order,
            $this->sheet_purchase_order_details,
            $this->sheet_warehouses
        ];

        if($this->filters['use_cost_center']) $sheets[] = $this->sheet_cost_centers;
        if($this->filters['is_rete_iva']) $sheets[] = $this->sheet_rete_iva;
        if($this->filters['is_rete_ica']) $sheets[] = $this->sheet_rete_ica;

        $sheets = [
            ...$sheets,
            $this->sheet_type_details,
            $this->sheet_imp_cargo,
            $this->sheet_imp_retencion
        ];

        return $sheets;
    }
}

<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InvoiceSiigoMultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $sheet_invoices;
    protected $sheet_invoices_details;

    public function __construct($sellers, $cost_centers, $invoices, $credit_notes, $purchases, $products, $stores, $documents)
    {
        $this->sheet_invoices = new InvoiceSiigoExport($sellers, $cost_centers, $invoices, $documents);
        $this->sheet_invoices_details = new InvoiceDetailSiigoExport($sellers, $cost_centers, $invoices, $credit_notes, $purchases, $products, $stores);
    }

    public function sheets(): array
    {
        return [
            $this->sheet_invoices,
            $this->sheet_invoices_details
        ];
    }
}

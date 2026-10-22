<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasiveTransferSiigoMultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $sheet_transfer;
    protected $sheet_transfer_details;
    protected $warehouses;

    public function __construct($transfer, $transfer_details, $warehouses = [])
    {
        $this->sheet_transfer = new MasiveTransferSiigoExport($transfer);
        $this->sheet_transfer_details = new MasiveTransferDetailSiigoExport($transfer_details);
        $this->warehouses = new MasiveTransferWarehousesSiigoExport($warehouses);
    }

    public function sheets(): array
    {
        return [
            $this->sheet_transfer,
            $this->sheet_transfer_details,
            $this->warehouses
        ];
    }
}

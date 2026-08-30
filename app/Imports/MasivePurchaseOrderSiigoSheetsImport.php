<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasivePurchaseOrderSiigoSheetsImport   implements WithMultipleSheets
{
    public function sheets() : array
    {
        return [
            'orden_compra' => new MasivePurchaseOrderSiigoImport(),
            'orden_compra_detalles' => new MasivePurchaseOrderDetailsSiigoImport()
        ];
    }
}

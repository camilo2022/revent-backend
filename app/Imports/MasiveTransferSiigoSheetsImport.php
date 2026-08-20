<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasiveTransferSiigoSheetsImport implements WithMultipleSheets
{
    public function sheets() : array
    {
        return [
            'traslado' => new MasiveTransferSiigoImport(),
            'traslado_detalles' => new MasiveTransferDetailsSiigoImport()
        ];
    }
}

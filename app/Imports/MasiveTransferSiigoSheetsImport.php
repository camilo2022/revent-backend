<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
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

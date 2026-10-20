<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasiveTransferSiigoSheetsImport implements WithMultipleSheets, SkipsUnknownSheets
{
    public function sheets() : array
    {
        return [
            'traslado' => new MasiveTransferSiigoImport(),
            'traslado_observaciones' => new MasiveTransferObservationsSiigoImport(),
            'traslado_detalles' => new MasiveTransferDetailsSiigoImport()
        ];
    }

    // Formatos viejos no traen la hoja traslado_observaciones: se ignora en vez de fallar
    public function onUnknownSheet($sheetName)
    {
    }
}

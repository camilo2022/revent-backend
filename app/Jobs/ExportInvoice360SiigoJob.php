<?php

namespace App\Jobs;

use App\Mail\ExportInvoice360SiigoMail;

/** Facturación 360: el reporte de facturación más la hoja con la lógica 360. */
class ExportInvoice360SiigoJob extends ExportInvoiceSiigoJob
{
    protected function includes360(): bool
    {
        return true;
    }

    protected function mailClass(): string
    {
        return ExportInvoice360SiigoMail::class;
    }
}

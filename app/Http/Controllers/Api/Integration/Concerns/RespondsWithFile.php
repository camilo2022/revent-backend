<?php

namespace App\Http\Controllers\Api\Integration\Concerns;

use Barryvdh\DomPDF\PDF;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Descargas binarias de la API de integración (xlsx / pdf): se entregan directo, sin envolver en JSON,
 * igual que ReportController::export. El front lee el nombre desde Content-Disposition.
 */
trait RespondsWithFile
{
    protected function downloadExcel(object $export, string $filename): BinaryFileResponse|Response
    {
        return Excel::download($export, $this->safeFilename($filename, 'xlsx'));
    }

    protected function downloadPdf(PDF $pdf, string $filename): Response
    {
        return $pdf->download($this->safeFilename($filename, 'pdf'));
    }

    private function safeFilename(string $filename, string $extension): string
    {
        $name = preg_replace('/[^\w\-. ]+/u', '_', pathinfo($filename, PATHINFO_FILENAME)) ?: 'archivo';

        return "{$name}.{$extension}";
    }
}

<?php

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Generación del Excel y envío del correo de éxito/fallo de los exportes Siigo.
 * El archivo se genera en una ruta temporal y se adjunta al correo; solo si supera
 * config('siigo.exports.max_attachment_mb') se deja en el disco público con enlace de
 * descarga. El job define mailClass() con su Mailable.
 */
trait SendsExportMail
{
    /** Clase Mailable del exporte (acepta success, filename, downloadUrl, errorMessage, attachmentPath, attachmentName). */
    abstract protected function mailClass(): string;

    /** Nombre de archivo: prefijo + fecha/hora actual. */
    protected function exportFilename(string $prefix, string $format = 'Y_m_d_His'): string
    {
        return $prefix . '_' . now()->format($format) . '.xlsx';
    }

    /** Genera el Excel en un temporal, lo envía (adjunto o enlace) y borra el temporal siempre. */
    protected function storeAndNotify(object $export, string $filename): void
    {
        $relative = 'tmp/exports/' . $filename;
        $local = Storage::disk('local');

        // Guarda el Excel en la carpeta temporal
        Excel::store($export, $relative, 'local');

        $tempPath = $local->path($relative);

        try {
            // Tamaño máximo para enviarlo adjunto (en bytes)
            $maxBytes = (int) config('siigo.exports.max_attachment_mb', 25) * 1024 * 1024;
            $mail = $this->mailClass();

            // Si cabe, se envía el Excel adjunto en el correo
            if (filesize($tempPath) <= $maxBytes) {
                Mail::to($this->notifyEmail)->send(new $mail(
                    success: true,
                    filename: $filename,
                    attachmentPath: $tempPath,
                    attachmentName: $filename
                ));

                return;
            }

            // Si es muy grande, se copia al disco público y el correo lleva el enlace de descarga
            $stream = fopen($tempPath, 'r');
            try {
                Storage::disk('public')->writeStream('exports/' . $filename, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            Mail::to($this->notifyEmail)->send(new $mail(
                success: true,
                filename: $filename,
                downloadUrl: route('exports.download', ['file' => $filename])
            ));
        } finally {
            // Borra el temporal, se haya enviado bien o no
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        $mail = $this->mailClass();

        // Avisa por correo que el exporte falló, con el mensaje del error
        Mail::to($this->notifyEmail)->send(new $mail(success: false, errorMessage: $e->getMessage()));
    }
}

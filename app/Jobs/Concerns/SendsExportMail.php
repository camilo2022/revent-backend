<?php

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Generación del Excel y envío del correo de éxito/fallo de los exportes Siigo.
 * El archivo se genera en una ruta temporal y se adjunta al correo; solo si supera
 * config('siigo.exports.max_attachment_mb') se deja en el disco público con enlace de
 * descarga (siigo:purge-exports lo limpia). El job define mailClass() con su Mailable.
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

        Excel::store($export, $relative, 'local');

        $tempPath = $local->path($relative);

        try {
            $maxBytes = (int) config('siigo.exports.max_attachment_mb', 20) * 1024 * 1024;
            $mail = $this->mailClass();

            if (filesize($tempPath) <= $maxBytes) {
                Mail::to($this->notifyEmail)->send(new $mail(
                    success: true,
                    filename: $filename,
                    attachmentPath: $tempPath,
                    attachmentName: $filename
                ));

                return;
            }

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
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        $mail = $this->mailClass();

        Mail::to($this->notifyEmail)->send(new $mail(success: false, errorMessage: $e->getMessage()));
    }
}

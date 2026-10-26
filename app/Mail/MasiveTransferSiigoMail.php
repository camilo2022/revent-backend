<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class MasiveTransferSiigoMail extends Mailable
{
    use Queueable, SerializesModels;

    private const MIME_PDF = 'application/pdf';
    private const MIME_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(
        public array $traslados,
        public array $errors = [],
        public string $template_view = 'email.masive_transfer.transfer',
        public bool $adjuntar_pdf = false,
        public bool $adjuntar_excel = false,
    ) {}

    public function build()
    {
        $this
            ->subject('Resultado de traslados masivos - ' . now()->format('d/m/Y H:i'))
            ->view($this->template_view);

        foreach ($this->traslados as $traslado) {
            if ($this->adjuntar_pdf) {
                $this->adjuntar($traslado['pdf'] ?? null, self::MIME_PDF);
            }

            if ($this->adjuntar_excel) {
                $this->adjuntar($traslado['approved'] ?? null, self::MIME_XLSX);
            }
        }

        return $this;
    }

    private function adjuntar(?array $archivo, string $mime): void
    {
        if (empty($archivo['disk']) || empty($archivo['path'])) {
            return;
        }

        if (!Storage::disk($archivo['disk'])->exists($archivo['path'])) {
            return;
        }

        $this->attachFromStorageDisk($archivo['disk'], $archivo['path'], $archivo['file'] ?? basename($archivo['path']), [
            'mime' => $mime,
        ]);
    }
}

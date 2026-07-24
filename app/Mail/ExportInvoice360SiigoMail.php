<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExportInvoice360SiigoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public bool $success,
        public ?string $filename = null,
        public ?string $downloadUrl = null,
        public ?string $errorMessage = null
    ) {}

    public function build()
    {
        return $this
            ->subject($this->success
                ? "Exportación de facturas 360 lista: {$this->filename}"
                : 'Error en exportación de facturas 360 Siigo')
            ->view('email.export-invoice-360-siigo');
    }
}

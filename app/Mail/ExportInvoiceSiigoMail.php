<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExportInvoiceSiigoMail extends Mailable
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
                ? "Exportación de facturas lista: {$this->filename}"
                : 'Error en exportación de facturas Siigo')
            ->view('email.export-invoice-siigo');
    }
}

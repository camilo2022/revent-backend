<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExportInventorySiigoMail extends Mailable
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
                ? "Exportación de inventario lista: {$this->filename}"
                : 'Error en exportación de inventario Siigo')
            ->view('email.export-inventory-siigo');
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExportPurchaseSiigoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public bool $success,
        public ?string $filename = null,
        public ?string $downloadUrl = null,
        public ?string $errorMessage = null,
        public ?string $attachmentPath = null,
        public ?string $attachmentName = null
    ) {}

    public function build()
    {
        $mail = $this
            ->subject($this->success
                ? "Exportación de compras lista: {$this->filename}"
                : 'Error en exportación de compras Siigo')
            ->view('email.export-purchase-siigo');

        if ($this->attachmentPath) {
            $mail->attach($this->attachmentPath, ['as' => $this->attachmentName ?? $this->filename]);
        }

        return $mail;
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MasiveTransferSiigoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $traslados,
        public array $errors = [],
        public string $template_view = 'email.masive-transfer-siigo'
    ) {}

    public function build()
    {
        return $this
            ->subject('Resultado de traslados masivos - ' . now()->format('d/m/Y H:i'))
            ->view($this->template_view);
    }
}

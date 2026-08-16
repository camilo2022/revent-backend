<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MasiveTransferSiigoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $traslados
    ) {}

    public function build()
    {
        return $this
            ->subject('Resultado de traslados masivos - ' . now()->format('d/m/Y H:i'))
            ->view('email.masive-transfer-siigo');
    }
}

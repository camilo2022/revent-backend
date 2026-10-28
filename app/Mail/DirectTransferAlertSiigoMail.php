<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DirectTransferAlertSiigoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $warehouse,
        public string $date,
        public array $transfers,
    ) {}

    public function build()
    {
        return $this
            ->subject("Alerta: traslados directos desde {$this->warehouse} - {$this->date}")
            ->view('email.direct_transfer_alert');
    }
}

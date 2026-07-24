<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SyncUnicoSiigoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public bool $success,
        public array $details
    ) {}

    public function build()
    {
        return $this->subject($this->success
                ? 'Sync Unico/Siigo: Completado'
                : 'Sync Unico/Siigo: Error')
            ->view('email.sync-unico-siigo');
    }
}

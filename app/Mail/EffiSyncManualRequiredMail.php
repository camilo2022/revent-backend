<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Aviso a la bodega WEB: la sincronización con Effi no se hizo sola y hay que hacerla manual porque hay decisiones por tomar. */
class EffiSyncManualRequiredMail extends Mailable
{
    use SerializesModels;

    public function __construct(public array $documents, public array $pending)
    {
    }

    public function build()
    {
        // Pruebas: si hay un destinatario de prueba, el correo va solo allí (no a la bodega WEB)
        if ($target = \App\Support\Integration\EffiMailRedirect::target()) {
            $this->to = [];
            $this->to($target);
        }

        return $this
            ->subject('Effi: hay que sincronizar la bodega WEB manualmente (hay decisiones por tomar)')
            ->view('email.effi_sync.manual', ['link' => route('siigo.effi_sync')]);
    }
}

<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Aviso a la bodega WEB: la sincronización con Effi no se pudo hacer (error o había otra en curso). No se cambió nada o quedó a medias. */
class EffiSyncFailedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public string $reason)
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
            ->subject('Effi: la sincronización de la bodega WEB no se pudo completar')
            ->view('email.effi_sync.failed', ['link' => route('siigo.effi_sync')]);
    }
}

<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InventoryFilterAccessLinkMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $url
    ) {}

    public function build()
    {
        return $this
            ->subject('Acceso a Filtro de inventarios REVENT CALZADO S.A.S.')
            ->view('email.access.inventory_filter');
    }
}

<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountPayableAccessLink extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $url
    ) {}

    public function build()
    {
        return $this
            ->subject('Acceso a Cuentas por pagar REVENT CALZADO S.A.S.')
            ->view('email.account-payable-access-link');
    }
}

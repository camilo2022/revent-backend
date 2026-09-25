<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoicePurchaseOrderAccessLink extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $url
    ) {}

    public function build()
    {
        return $this
            ->subject('Acceso a Ordenes de compra REVENT CALZADO S.A.S.')
            ->view('email.invoice-purchase-order-access-link');
    }
}

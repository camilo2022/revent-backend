<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoicePurchaseOrderConfirmedSiigo extends Mailable
{
    use SerializesModels;

    public function __construct(
        public array $data
    ) {}

    public function build()
    {
        return $this
            ->subject('Confirmacion de recepcion de orden de compra REVENT CALZADO S.A.S.')
            ->view('email.invoice-purchase-order-confirmed');
    }
}

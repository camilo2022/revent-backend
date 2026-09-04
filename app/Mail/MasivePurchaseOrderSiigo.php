<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MasivePurchaseOrderSiigo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $ordenes_compra,
        public array $errors = []
    ) {}

    public function build()
    {
        return $this
            ->subject('Órdenes de compra REVENT CALZADO S.A.S.')
            ->view('email.masive-purchase-order-siigo');
    }
}

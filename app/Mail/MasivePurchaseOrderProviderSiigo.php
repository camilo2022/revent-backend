<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MasivePurchaseOrderProviderSiigo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $ordenes_compra,
        public Collection $files,
        public string $referencia,
        public array $provider,
    ) {}

    public function build()
    {
        return $this
            ->subject('Órdenes de compra REVENT CALZADO S.A.S.')
            ->view('email.masive-purchase-order-provider-siigo');
    }
}

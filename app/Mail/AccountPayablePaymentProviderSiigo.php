<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountPayablePaymentProviderSiigo extends Mailable
{
    use SerializesModels;

    public function __construct(
        public array $provider,
        public array $voucher,
        public array $documentos,
        public array $firma,
        public string|null $observaciones,
        public int|string $voucher_id,
        public string|null $url
    ) {}

    public function build()
    {
        return $this
            ->subject('Comprobante de pago REVENT CALZADO S.A.S.')
            ->view('email.account-payable-payment-provider-siigo');
    }
}

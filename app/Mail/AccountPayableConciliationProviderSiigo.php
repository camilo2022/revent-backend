<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountPayableConciliationProviderSiigo extends Mailable
{
    use SerializesModels;

    public function __construct(
        public array $provider,
        public array $voucher,
        public array $documentos,
        public array $recibos,
        public array $firma,
        public string|null $observaciones,
        public int|string $voucher_id
    ) {}

    public function build()
    {
        return $this
            ->subject('Comprobante de cruce de saldos REVENT CALZADO S.A.S.')
            ->view('email.account-payable-conciliation-provider-siigo');
    }
}

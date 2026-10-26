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
        public string|null $url = null,
        public string|null $attachmentPath = null,
        public string|null $attachmentName = null
    ) {}

    public function build()
    {
        $mail = $this
            ->subject('Comprobante de pago REVENT CALZADO S.A.S.')
            ->view('email.account_payable.payment');

        if ($this->attachmentPath) {
            $mail->attach($this->attachmentPath, ['as' => $this->attachmentName]);
        }

        return $mail;
    }
}

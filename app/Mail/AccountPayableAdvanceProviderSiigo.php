<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountPayableAdvanceProviderSiigo extends Mailable
{
    use SerializesModels;

    public function __construct(
        public array $provider,
        public array $voucher,
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
            ->subject('Comprobante de anticipo REVENT CALZADO S.A.S.')
            ->view('email.account_payable.advance');

        if ($this->attachmentPath) {
            $mail->attach($this->attachmentPath, ['as' => $this->attachmentName]);
        }

        return $mail;
    }
}

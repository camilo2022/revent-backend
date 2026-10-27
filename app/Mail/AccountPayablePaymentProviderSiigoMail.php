<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountPayablePaymentProviderSiigoMail extends Mailable
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

        // Una imagen viaja incrustada en el cuerpo del correo; cualquier otro archivo, como adjunto.
        $isImage = $this->attachmentPath
            && in_array(strtolower(pathinfo($this->attachmentPath, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

        $mail->with(['receiptIsImage' => $isImage]);

        if ($this->attachmentPath && !$isImage) {
            $mail->attach($this->attachmentPath, ['as' => $this->attachmentName]);
        }

        return $mail;
    }
}

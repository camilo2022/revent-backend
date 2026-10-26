<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MasivePurchaseOrderProviderSiigo extends Mailable
{
    use Queueable, SerializesModels;

    private const MIME_PDF = 'application/pdf';

    public function __construct(
        public array $ordenes_compra,
        public Collection $files,
        public string $referencia,
        public array $provider,
    ) {}

    public function build()
    {
        $this
            ->subject('Órdenes de compra REVENT CALZADO S.A.S.')
            ->view('email.masive_purchase_order.provider');

        foreach ($this->ordenes_compra as $orden) {
            $this->adjuntar($orden['pdf'] ?? null);
        }

        return $this;
    }

    private function adjuntar(?array $archivo): void
    {
        if (empty($archivo['disk']) || empty($archivo['path'])) {
            return;
        }

        if (!Storage::disk($archivo['disk'])->exists($archivo['path'])) {
            return;
        }

        $this->attachFromStorageDisk($archivo['disk'], $archivo['path'], $archivo['file'] ?? basename($archivo['path']), [
            'mime' => self::MIME_PDF,
        ]);
    }
}

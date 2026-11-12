<?php

namespace App\Http\Resources\Api\Integration\AccountPayable;

use Illuminate\Http\Resources\Json\JsonResource;

/** HTML del comprobante de pago tal como lo entrega Siigo (el front lo muestra en un iframe aislado). */
class PaymentHtmlResource extends JsonResource
{
    public function toArray($request)
    {
        return ['html' => (string) $this->resource];
    }
}

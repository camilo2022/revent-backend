<?php

namespace App\Http\Resources\Api\Integration\AccountPayable;

use Illuminate\Http\Resources\Json\JsonResource;

/** Comprobante registrado en Siigo (pago, anticipo o cruce contable). */
class VoucherResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'voucher' => [
                'voucher_id' => $this->resource['voucher_id'],
                'voucher' => $this->resource['voucher'],
            ],
        ];
    }
}

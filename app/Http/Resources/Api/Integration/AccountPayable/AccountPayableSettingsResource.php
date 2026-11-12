<?php

namespace App\Http\Resources\Api\Integration\AccountPayable;

use Illuminate\Http\Resources\Json\JsonResource;

/** Datos para armar los formularios de pago: tipo de recibo, cuentas bancarias y tipos de documento. */
class AccountPayableSettingsResource extends JsonResource
{
    public function toArray($request)
    {
        return ['settings' => self::map($this->resource)];
    }

    public static function map(array $settings): array
    {
        return [
            'type_receipt' => $settings['type_receipt'],
            'bank_accounts' => $settings['bank_accounts'],
            'type_documents' => $settings['type_documents'],
        ];
    }
}

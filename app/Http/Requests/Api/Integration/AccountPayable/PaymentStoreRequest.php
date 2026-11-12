<?php

namespace App\Http\Requests\Api\Integration\AccountPayable;

use Illuminate\Validation\Validator;

class PaymentStoreRequest extends VoucherRequest
{
    public function rules(): array
    {
        return $this->provider() + [
            'tipo' => ['required', 'integer'],
            'accion' => ['required', 'string', 'max:100'],
            'origen' => ['required', 'integer'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*.doc' => ['required', 'array'],
            'documentos.*.doc.DueName' => ['required', 'string', 'max:100', 'distinct'],
            'documentos.*.doc.ACEntryID' => ['required', 'integer', 'min:1'],
            'documentos.*.covered' => ['required', 'numeric', 'min:0.01'],
            'comprobante' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->sumOf('documentos', 'covered') !== round((float) $this->input('valor'), self::DECIMALS)) {
                $validator->errors()->add('valor', 'El valor a pagar debe ser igual a la suma de lo aplicado a cada documento.');
            }
        }];
    }
}

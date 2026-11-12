<?php

namespace App\Http\Requests\Api\Integration\AccountPayable;

use Illuminate\Validation\Validator;

class ConciliationStoreRequest extends VoucherRequest
{
    public function rules(): array
    {
        return $this->provider() + [
            'tipo' => ['required', 'integer'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*.doc' => ['required', 'array'],
            'documentos.*.doc.DueName' => ['required', 'string', 'max:100', 'distinct'],
            'documentos.*.doc.ACEntryID' => ['required', 'integer', 'min:1'],
            'documentos.*.covered' => ['required', 'numeric', 'min:0.01'],
            'recibos' => ['required', 'array', 'min:1'],
            'recibos.*.doc' => ['required', 'array'],
            'recibos.*.doc.DueName' => ['required', 'string', 'max:100', 'distinct'],
            'recibos.*.used' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->sumOf('documentos', 'covered') !== $this->sumOf('recibos', 'used')) {
                $validator->errors()->add('recibos', 'Lo usado de los recibos debe ser igual a lo aplicado a los documentos.');
            }
        }];
    }
}

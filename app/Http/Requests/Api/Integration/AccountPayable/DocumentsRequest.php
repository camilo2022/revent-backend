<?php

namespace App\Http\Requests\Api\Integration\AccountPayable;

use App\Http\Requests\Api\Integration\IntegrationRequest;

class DocumentsRequest extends IntegrationRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['accountId' => $this->route('accountId')]);
    }

    public function rules(): array
    {
        return [
            'accountId' => ['required', 'integer', 'min:1'],
            'MsThirdPartyID' => ['required', 'string', 'max:50'],
            'all_data' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + [
            'accountId' => 'Cuenta del proveedor',
            'MsThirdPartyID' => 'Identificador del tercero',
            'all_data' => 'Todos los datos',
        ];
    }
}

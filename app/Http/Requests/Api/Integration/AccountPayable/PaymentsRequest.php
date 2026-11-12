<?php

namespace App\Http\Requests\Api\Integration\AccountPayable;

use App\Http\Requests\Api\Integration\IntegrationRequest;

class PaymentsRequest extends IntegrationRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['accountId' => $this->route('accountId')]);
    }

    public function rules(): array
    {
        return [
            'accountId' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['accountId' => 'Cuenta del proveedor'];
    }
}

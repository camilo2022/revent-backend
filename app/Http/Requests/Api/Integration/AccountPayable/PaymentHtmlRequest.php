<?php

namespace App\Http\Requests\Api\Integration\AccountPayable;

use App\Http\Requests\Api\Integration\IntegrationRequest;

class PaymentHtmlRequest extends IntegrationRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['acEntryId' => $this->route('acEntryId')]);
    }

    public function rules(): array
    {
        return [
            'acEntryId' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['acEntryId' => 'Comprobante'];
    }
}

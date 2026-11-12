<?php

namespace App\Http\Requests\Api\Integration\TransferApproval;

use App\Http\Requests\Api\Integration\IntegrationRequest;

class SearchRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', $this->siigoToken()],
            'query' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['query' => 'nota de traslado'];
    }
}

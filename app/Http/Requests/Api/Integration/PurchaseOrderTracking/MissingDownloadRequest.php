<?php

namespace App\Http\Requests\Api\Integration\PurchaseOrderTracking;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Excel de referencias faltantes: no recibe datos. */
class MissingDownloadRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}

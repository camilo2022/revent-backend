<?php

namespace App\Http\Requests\Api\Integration\PurchaseOrderTracking;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Seguimiento de órdenes de compra: no recibe datos (usa los últimos 5 meses). */
class DocumentsRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}

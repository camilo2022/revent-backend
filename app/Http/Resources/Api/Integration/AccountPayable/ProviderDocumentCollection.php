<?php

namespace App\Http\Resources\Api\Integration\AccountPayable;

use Illuminate\Http\Resources\Json\JsonResource;

/** Documentos por pagar de un proveedor, enlazados con su compra y orden de compra. */
class ProviderDocumentCollection extends JsonResource
{
    public function toArray($request)
    {
        return ['documents' => array_values($this->resource)];
    }
}

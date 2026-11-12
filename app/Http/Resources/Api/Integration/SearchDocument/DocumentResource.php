<?php

namespace App\Http\Resources\Api\Integration\SearchDocument;

use Illuminate\Http\Resources\Json\JsonResource;

/** Documento de Siigo con sus productos; `document` es null cuando no existe. */
class DocumentResource extends JsonResource
{
    public function toArray($request)
    {
        return ['document' => $this->resource];
    }
}

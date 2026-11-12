<?php

namespace App\Http\Resources\Api\Integration\ProductPhoto;

use Illuminate\Http\Resources\Json\JsonResource;

/** Resultado de la carga masiva de fotos desde un ZIP. */
class BulkUploadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'bulk_upload' => [
                'references_processed' => (int) $this->resource['references_processed'],
                'images_uploaded' => (int) $this->resource['images_uploaded'],
                'empty_folders' => (int) $this->resource['empty_folders'],
            ],
        ];
    }
}

<?php

namespace App\Http\Resources\Api\Integration\ProductPhoto;

use Illuminate\Http\Resources\Json\JsonResource;

/** Fotos subidas a la carpeta de una referencia. */
class PhotoUploadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'upload' => [
                'referencia' => $this->resource['referencia'],
                'uploaded' => collect($this->resource['uploaded'])->map(fn ($photo) => PhotoCollection::map($photo))->values()->all(),
            ],
        ];
    }
}

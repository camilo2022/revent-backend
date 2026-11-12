<?php

namespace App\Http\Resources\Api\Integration\ProductPhoto;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fotos subidas desde el explorador y, si la carpeta era de un producto o de un color, el resultado del envío a Siigo
 * (un fallo de Siigo no deshace lo guardado).
 */
class ExplorerUploadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'upload' => [
                'path' => $this->resource['path'],
                'uploaded' => collect($this->resource['uploaded'])->map(fn ($photo) => PhotoCollection::map($photo))->values()->all(),
                'siigo' => $this->resource['siigo'] ?? null,
            ],
        ];
    }
}

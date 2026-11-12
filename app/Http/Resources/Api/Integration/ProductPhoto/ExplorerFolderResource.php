<?php

namespace App\Http\Resources\Api\Integration\ProductPhoto;

use Illuminate\Http\Resources\Json\JsonResource;

/** Carpeta recién creada en el explorador. */
class ExplorerFolderResource extends JsonResource
{
    public function toArray($request)
    {
        return ['folder' => ['path' => $this->resource['path']]];
    }
}

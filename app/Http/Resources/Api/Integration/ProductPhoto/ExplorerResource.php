<?php

namespace App\Http\Resources\Api\Integration\ProductPhoto;

use Illuminate\Http\Resources\Json\JsonResource;

/** Contenido de una carpeta del explorador: migas de pan, carpetas, fotos y colores que aún se pueden crear. */
class ExplorerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'explorer' => [
                'path' => $this->resource['path'],
                'level' => (int) $this->resource['level'],
                'breadcrumb' => collect($this->resource['breadcrumb'])->values()->all(),
                'folders' => collect($this->resource['folders'])->values()->all(),
                'files' => collect($this->resource['files'])->map(fn ($photo) => PhotoCollection::map($photo))->values()->all(),
                'available_colors' => collect($this->resource['available_colors'])->values()->all(),
            ],
        ];
    }
}

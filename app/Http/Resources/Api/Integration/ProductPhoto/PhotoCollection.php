<?php

namespace App\Http\Resources\Api\Integration\ProductPhoto;

use Illuminate\Http\Resources\Json\JsonResource;

/** Fotos de una referencia: cada una con su nombre, url y peso. */
class PhotoCollection extends JsonResource
{
    public static function map($photo): array
    {
        return [
            'name' => $photo['name'],
            'url' => $photo['url'],
            'size' => (int) $photo['size'],
        ];
    }

    public function toArray($request)
    {
        return [
            'referencia' => $this->resource['referencia'],
            'exists' => (bool) $this->resource['exists'],
            'photos' => collect($this->resource['photos'])->map(fn ($photo) => self::map($photo))->values()->all(),
        ];
    }
}

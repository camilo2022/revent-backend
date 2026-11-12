<?php

namespace App\Http\Resources\Api\Integration\InventoryFilter;

use Illuminate\Http\Resources\Json\JsonResource;

/** Una página de fotos de una referencia. */
class InventoryImagesResource extends JsonResource
{
    public static function map($page): array
    {
        return [
            'images' => $page['images'],
            'page' => $page['page'],
            'per_page' => $page['per_page'],
            'total' => $page['total'],
            'last_page' => $page['last_page'],
            'has_previous' => $page['has_previous'],
            'has_next' => $page['has_next'],
        ];
    }

    public function toArray($request)
    {
        return ['images' => self::map($this->resource)];
    }
}

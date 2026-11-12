<?php

namespace App\Http\Resources\Api\Integration\SearchDocument;

use Illuminate\Http\Resources\Json\JsonResource;

/** Tipos de documento que se pueden buscar y la credencial de Siigo que exige cada uno. */
class DocumentTypeCollection extends JsonResource
{
    public function toArray($request)
    {
        return ['types' => collect($this->resource)->map(fn ($type, $id) => self::map($id, $type))->values()->all()];
    }

    public static function map(int $id, array $type): array
    {
        return [
            'id' => $id,
            'name' => $type['name'],
            'credential' => $type['credential'],
            'hint' => $type['hint'],
        ];
    }
}

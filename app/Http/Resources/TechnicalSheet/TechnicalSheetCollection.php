<?php

namespace App\Http\Resources\TechnicalSheet;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TechnicalSheetCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'technical_sheets' => $this->collection->map(fn ($sheet) => TechnicalSheetResource::map($sheet)),
            'meta' => [
                'pagination' => [
                    'total' => $this->total(),
                    'count' => $this->count(),
                    'per_page' => $this->perPage(),
                    'current_page' => $this->currentPage(),
                    'total_pages' => $this->lastPage(),
                ],
            ],
        ];
    }
}

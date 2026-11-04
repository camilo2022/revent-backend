<?php

namespace App\Http\Resources\Report;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ReportCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'reports' => $this->collection->map(fn ($report) => ReportResource::map($report)),
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

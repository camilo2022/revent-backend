<?php

namespace App\Http\Resources\Classification\Subcategory;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubcategoryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'subcategories' => $this->collection->map(function ($subcategory) {
                return [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'description' => $subcategory->description,
                    'settings' => $subcategory->settings,
                    'created_at' => $this->formatDate($subcategory->created_at),
                    'updated_at' => $this->formatDate($subcategory->updated_at),
                    'deleted_at' => $this->formatDate($subcategory->deleted_at),
                    'categories' => $subcategory->categories,
                ];
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    protected function paginationMeta()
    {
        return [
            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage(),
        ];
    }
}

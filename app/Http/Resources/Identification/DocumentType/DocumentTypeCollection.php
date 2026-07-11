<?php

namespace App\Http\Resources\Identification\DocumentType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DocumentTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'document_types' => $this->collection->map(function ($document_type) {
                return [
                    'id' => $document_type->id,
                    'name' => $document_type->name,
                    'description' => $document_type->description,
                    'settings' => $document_type->settings,
                    'created_at' => $this->formatDate($document_type->created_at),
                    'updated_at' => $this->formatDate($document_type->updated_at),
                    'deleted_at' => $this->formatDate($document_type->deleted_at),
                    'item' => $document_type->item,
                    'person_type' => $document_type->person_type->first()
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

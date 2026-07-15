<?php

namespace App\Http\Resources\Supplier;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'suppliers' => $this->collection->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'code' => $supplier->code,
                    'legal_name' => $supplier->legal_name,
                    'trade_name' => $supplier->trade_name,
                    'document_type_id' => $supplier->document_type_id,
                    'document' => $supplier->document,
                    'location_id' => $supplier->location_id,
                    'location_type' => $supplier->location_type,
                    'address' => $supplier->address,
                    'neighborhood' => $supplier->neighborhood,
                    'phone_country_id' => $supplier->phone_country_id,
                    'phone' => $supplier->phone,
                    'email' => $supplier->email,
                    'created_at' => $this->formatDate($supplier->created_at),
                    'updated_at' => $this->formatDate($supplier->updated_at),
                    'deleted_at' => $this->formatDate($supplier->deleted_at),
                    'document_type' => $supplier->document_type,
                    'location' => $supplier->location,
                    'phone_country' => $supplier->phone_country
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

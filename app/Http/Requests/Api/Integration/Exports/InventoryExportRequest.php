<?php

namespace App\Http\Requests\Api\Integration\Exports;

class InventoryExportRequest extends ExportRequest
{
    public function rules(): array
    {
        return [
            ...$this->emailRules(),
            'inventory_type' => ['nullable', 'string', 'in:positivo,negativo'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'type' => ['nullable', 'string', 'in:Product,Variant'],
        ];
    }
}

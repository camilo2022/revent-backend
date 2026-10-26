<?php

namespace App\Http\Requests\Integration\ProductTraceability;

use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadRequest extends FormRequest
{
    /** Bodegas de Siigo, consultadas una sola vez por solicitud (las usan la regla y el controlador). */
    private ?array $warehouses = null;

    public function authorize(): bool
    {
        return true;
    }

    /** Bodegas tal como las entrega Siigo. */
    public function warehouses(): array
    {
        return $this->warehouses ??= app(SiigoCatalogService::class)->warehouses(app(SiigoClient::class)->token());
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'string', Rule::in(collect($this->warehouses())->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'start_date' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'in' => 'La bodega seleccionada no es válida.',
            'date' => 'Debe ser una fecha válida.',
            'file' => 'Debe ser un archivo.',
            'mimes' => 'El archivo debe ser de tipo: :values.',
            'max.file' => 'El archivo no debe superar :max KB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'Bodega',
            'start_date' => 'Fecha inicial',
            'file' => 'Archivo de Excel',
        ];
    }
}

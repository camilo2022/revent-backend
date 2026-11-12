<?php

namespace App\Http\Requests\Api\Integration\ProductTraceability;

use App\Http\Requests\Api\Integration\IntegrationRequest;
use App\Rules\Integration\AccessibleWarehouse;
use App\Services\Integration\ProductTraceabilityAuditService;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Datos de la trazabilidad: bodega (a la que el usuario tenga acceso), fecha inicial y Excel con la columna "codigo".
 * Copiado de Integration\ProductTraceability\DownloadRequest; el archivo se revisa antes de consultar a Siigo.
 */
class DownloadRequest extends IntegrationRequest
{
    /** Bodegas de Siigo, consultadas una sola vez por solicitud (las usan la regla y el controlador). */
    private ?array $warehouses = null;

    /** Códigos de la columna "codigo" del archivo, leídos una sola vez. */
    private ?array $codes = null;

    /** Bodegas tal como las entrega Siigo. */
    public function warehouses(): array
    {
        return $this->warehouses ??= app(SiigoCatalogService::class)->warehouses(app(SiigoClient::class)->token());
    }

    /** Códigos de producto del archivo (en mayúsculas, sin repetir). */
    public function codes(): array
    {
        return $this->codes ??= app(ProductTraceabilityAuditService::class)->codesFromFile($this->file('file'));
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => [
                'required',
                'string',
                Rule::in(collect($this->warehouses())->pluck('id')->map(fn ($id) => (string) $id)->all()),
                new AccessibleWarehouse(),
            ],
            'start_date' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            try {
                $hasCodes = (bool) $this->codes();
            } catch (\Throwable $e) {
                $validator->errors()->add('file', 'No se pudo leer el archivo de Excel. Descarga la plantilla y vuelve a intentarlo.');

                return;
            }

            if (!$hasCodes) {
                $validator->errors()->add('file', 'El archivo no tiene códigos en la columna "codigo".');
            }
        }];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'warehouse_id.in' => 'La bodega seleccionada no es válida.',
            'file.mimes' => 'El archivo debe ser de tipo: :values.',
            'file.max' => 'El archivo no debe superar :max KB.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'warehouse_id' => 'Bodega',
            'start_date' => 'Fecha inicial',
            'file' => 'Archivo de Excel',
        ];
    }
}

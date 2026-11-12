<?php

namespace App\Http\Requests\Api\Integration\MasivePurchaseOrders;

use App\Http\Requests\Api\Integration\IntegrationRequest;
use App\Imports\MasivePurchaseOrderSiigoSheetsImport;
use App\Rules\Integration\AccessibleWarehouse;
use App\Rules\Integration\SiigoMonolithCookie;
use App\Support\WarehouseOption;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Carga de órdenes de compra masivas: archivo Excel con las hojas `orden_compra` y `orden_compra_detalles`,
 * correo de aviso y referencia de las fotos. Valida el contenido del Excel (hojas, columnas, valores y la cookie
 * que trae) y que el usuario tenga acceso a las bodegas de los detalles.
 */
class UploadRequest extends IntegrationRequest
{
    public const MAX_ERRORS = 20;

    /** Datos que debe traer la primera fila de la hoja orden_compra. */
    private const HEADER_COLUMNS = ['cookie', 'tipo', 'fecha', 'proveedor'];

    /** Columnas obligatorias de la hoja orden_compra_detalles. */
    private const DETAIL_COLUMNS = ['tipo_detalle', 'item', 'bodega', 'cantidad', 'valor_unitario'];

    private ?Collection $order = null;

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'email' => ['required', 'email', 'max:255'],
            'referencia' => ['required', 'string', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->workbookErrors() as $message) {
                $validator->errors()->add('file', $message);
            }
        }];
    }

    /** Hojas del Excel con las bodegas, impuestos y centro de costo ya convertidos a su id, como las recibe el job. */
    public function order(): Collection
    {
        return $this->order ??= $this->readWorkbook();
    }

    private function readWorkbook(): Collection
    {
        $order = Excel::toCollection(new MasivePurchaseOrderSiigoSheetsImport, $this->file('file'));

        $order = WarehouseOption::normalize($order, 'orden_compra', ['centro_costo', 'rete_iva', 'rete_ica']);

        return WarehouseOption::normalize($order, 'orden_compra_detalles', ['tipo_detalle', 'bodega', 'imp_cargo', 'imp_retencion']);
    }

    /** @return string[] */
    private function workbookErrors(): array
    {
        try {
            $order = $this->order();
        } catch (\Throwable $e) {
            return ['No se pudo leer el archivo de Excel. Descarga el formato y vuelve a intentarlo.'];
        }

        if (!$order->has('orden_compra') || !$order->has('orden_compra_detalles')) {
            return ['El archivo debe tener las hojas "orden_compra" y "orden_compra_detalles". Descarga el formato.'];
        }

        $errors = [];

        $header = $this->rowsWithData($order['orden_compra'])->first();

        if (!$header) {
            $errors[] = 'La hoja "orden_compra" no tiene datos.';
        } else {
            $errors = [...$errors, ...$this->headerErrors($header)];
        }

        $details = $this->rowsWithData($order['orden_compra_detalles']);

        if (!$details->count()) {
            return [...$errors, 'La hoja "orden_compra_detalles" no tiene filas.'];
        }

        $missing = array_values(array_filter(self::DETAIL_COLUMNS, fn ($column) => !$details->first()->has($column)));

        if ($missing) {
            return [...$errors, 'A la hoja "orden_compra_detalles" le faltan las columnas: ' . implode(', ', $missing) . '.'];
        }

        $errors = [...$errors, ...$this->detailErrors($details)];

        return array_slice([...$errors, ...$this->warehouseErrors($details)], 0, self::MAX_ERRORS);
    }

    /** @return string[] */
    private function headerErrors($header): array
    {
        $errors = [];

        foreach (self::HEADER_COLUMNS as $column) {
            if (!isset($header[$column]) || trim((string) $header[$column]) === '') {
                $errors[] = 'Hoja "orden_compra": falta "' . $column . '".';
            }
        }

        // La cookie viaja dentro del Excel (la escribe el formato); se revisa que tenga la forma esperada
        if (isset($header['cookie']) && trim((string) $header['cookie']) !== '') {
            (new SiigoMonolithCookie(false))->validate('cookie', (string) $header['cookie'], function (string $message) use (&$errors) {
                $errors[] = 'Hoja "orden_compra": ' . $message;
            });
        }

        return $errors;
    }

    /** @return string[] */
    private function detailErrors(Collection $details): array
    {
        $errors = [];

        foreach ($details as $number => $row) {
            $line = 'Hoja "orden_compra_detalles", fila ' . ($number + 2) . ': ';

            foreach (self::DETAIL_COLUMNS as $column) {
                if (!isset($row[$column]) || trim((string) $row[$column]) === '') {
                    $errors[] = $line . 'falta "' . $column . '".';
                }
            }

            if (isset($row['tipo_detalle']) && trim((string) $row['tipo_detalle']) !== '' && !in_array((string) $row['tipo_detalle'], ['0', '1', '2'], true)) {
                $errors[] = $line . 'el tipo de detalle no es válido (0 producto, 1 activo fijo, 2 gasto).';
            }

            foreach (['cantidad', 'valor_unitario'] as $column) {
                if (isset($row[$column]) && trim((string) $row[$column]) !== '' && !is_numeric($row[$column])) {
                    $errors[] = $line . '"' . $column . '" no es un número.';
                }
            }

            if (isset($row['cantidad']) && is_numeric($row['cantidad']) && (float) $row['cantidad'] <= 0) {
                $errors[] = $line . 'la cantidad debe ser mayor a cero.';
            }
        }

        return $errors;
    }

    /** Una sola vez por bodega: el usuario debe tener acceso a cada bodega de los detalles. */
    private function warehouseErrors(Collection $details): array
    {
        $errors = [];

        foreach ($details->pluck('bodega')->filter(fn ($value) => $value !== null && trim((string) $value) !== '')->unique() as $warehouse) {
            (new AccessibleWarehouse())->validate('bodega', $warehouse, function (string $message) use (&$errors) {
                $errors[] = $message;
            });
        }

        return $errors;
    }

    /** Filas que traen algún dato (el Excel puede traer filas vacías al final). La llave es la posición original. */
    private function rowsWithData(Collection $rows): Collection
    {
        return $rows->filter(fn ($row) => collect($row)->contains(fn ($value) => $value !== null && trim((string) $value) !== ''));
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'file.mimes' => 'El archivo debe ser de tipo Excel (.xlsx o .xls).',
            'file.max' => 'El archivo no puede pesar más de :max KB.',
            'referencia.required' => 'Escribe la referencia de las fotos.',
            'max.string' => 'Se permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'file' => 'Archivo',
            'email' => 'Correo',
            'referencia' => 'Referencia',
        ];
    }
}

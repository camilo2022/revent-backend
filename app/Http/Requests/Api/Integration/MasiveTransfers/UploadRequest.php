<?php

namespace App\Http\Requests\Api\Integration\MasiveTransfers;

use App\Http\Requests\Api\Integration\IntegrationRequest;
use App\Imports\MasiveTransferSiigoSheetsImport;
use App\Rules\Integration\AccessibleWarehouse;
use App\Support\WarehouseOption;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Carga de traslados masivos: archivo Excel con las hojas `traslado` y `traslado_detalles` y correo del dominio.
 * Valida el contenido del Excel (hojas, columnas, valores) y que el usuario tenga acceso a las bodegas de salida y entrada.
 */
class UploadRequest extends IntegrationRequest
{
    public const MAX_ERRORS = 20;

    /** Columnas obligatorias de la hoja traslado_detalles. */
    private const DETAIL_COLUMNS = ['codigo', 'bodega_salida', 'bodega_entrada', 'cantidad'];

    private ?Collection $transfer = null;

    public function rules(): array
    {
        $domain = preg_quote((string) config('siigo.mail.domain'), '/');

        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'email' => ['required', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@' . $domain . '$/'],
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

    /** Hojas del Excel con las bodegas ya convertidas a su id, tal como las recibe el job. */
    public function transfer(): Collection
    {
        return $this->transfer ??= $this->readWorkbook();
    }

    private function readWorkbook(): Collection
    {
        $sheets = Excel::toCollection(new MasiveTransferSiigoSheetsImport, $this->file('file'));

        return WarehouseOption::normalize($sheets, 'traslado_detalles', ['bodega_salida', 'bodega_entrada']);
    }

    /** @return string[] */
    private function workbookErrors(): array
    {
        try {
            $transfer = $this->transfer();
        } catch (\Throwable $e) {
            return ['No se pudo leer el archivo de Excel. Descarga el formato y vuelve a intentarlo.'];
        }

        $errors = [];

        if (!$transfer->has('traslado') || !$transfer->has('traslado_detalles')) {
            return ['El archivo debe tener las hojas "traslado" y "traslado_detalles". Descarga el formato.'];
        }

        if (!$this->rowsWithData($transfer['traslado'])->count()) {
            $errors[] = 'La hoja "traslado" no tiene datos.';
        }

        $details = $this->rowsWithData($transfer['traslado_detalles']);

        if (!$details->count()) {
            return [...$errors, 'La hoja "traslado_detalles" no tiene filas.'];
        }

        $missing = array_values(array_filter(self::DETAIL_COLUMNS, fn ($column) => !$details->first()->has($column)));

        if ($missing) {
            return [...$errors, 'A la hoja "traslado_detalles" le faltan las columnas: ' . implode(', ', $missing) . '.'];
        }

        foreach ($details as $number => $row) {
            foreach (self::DETAIL_COLUMNS as $column) {
                if (!isset($row[$column]) || trim((string) $row[$column]) === '') {
                    $errors[] = 'Hoja "traslado_detalles", fila ' . ($number + 2) . ': falta "' . $column . '".';
                }
            }

            if (isset($row['cantidad']) && trim((string) $row['cantidad']) !== '' && !is_numeric($row['cantidad'])) {
                $errors[] = 'Hoja "traslado_detalles", fila ' . ($number + 2) . ': la cantidad no es un número.';
            }
        }

        return array_slice([...$errors, ...$this->warehouseErrors($details)], 0, self::MAX_ERRORS);
    }

    /**
     * Una sola vez por bodega: solo se puede sacar mercancía de las bodegas de las tiendas del usuario (salida).
     * La entrada puede ser cualquier bodega, porque una tienda envía a las demás; quien tiene acceso a todas lo ve todo.
     */
    private function warehouseErrors(Collection $details): array
    {
        $errors = [];

        foreach ($details->pluck('bodega_salida')->filter(fn ($value) => $value !== null && $value !== '')->unique() as $warehouse) {
            (new AccessibleWarehouse())->validate('bodega_salida', $warehouse, function (string $message) use (&$errors) {
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
            'email.regex' => 'El correo :input debe pertenecer al dominio @' . config('siigo.mail.domain'),
            'file.max' => 'El archivo no puede pesar más de :max KB.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'file' => 'Archivo',
            'email' => 'Correo',
        ];
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * En los formatos de carga masiva las bodegas se listan como "ID-NOMBRE" (ej. "49-VENTURA PLAZA").
 * Al cargar el archivo se toma solo el ID (posición 0); un valor que ya es el ID queda igual.
 */
class WarehouseOption
{
    public static function id(mixed $value): mixed
    {
        if (!is_string($value) || !str_contains($value, '-')) {
            return $value;
        }

        // El ID puede ser negativo ("-1-SIN ASIGNAR"), por eso no sirve un explode simple.
        return preg_match('/^\s*(-?\d+)\s*-/', $value, $matches) ? (int) $matches[1] : $value;
    }

    /**
     * @param Collection $sheets hojas leídas del Excel, por nombre de hoja
     * @param string[] $columns columnas de la hoja que traen bodegas
     */
    public static function normalize(Collection $sheets, string $sheet, array $columns): Collection
    {
        if (!$sheets->has($sheet)) {
            return $sheets;
        }

        return $sheets->put($sheet, $sheets[$sheet]->map(function ($row) use ($columns) {
            foreach ($columns as $column) {
                if (isset($row[$column])) {
                    $row[$column] = self::id($row[$column]);
                }
            }

            return $row;
        }));
    }
}

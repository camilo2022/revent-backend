<?php

namespace App\Services;

use App\Models\Report;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ejecuta un reporte: arma la consulta del modelo configurado, aplica los filtros que el usuario eligió
 * y devuelve solo los campos que pidió (columnas + filas).
 */
class ReportService
{
    /** Campos configurados del reporte, por data_key. */
    public function configuredFields(Report $report): array
    {
        $fields = [];

        foreach ($report->settings->fields ?? [] as $group) {
            foreach ($group->fields ?? [] as $field) {
                $fields[$field->data_key] = [
                    'label' => $field->label,
                    'group' => $group->name ?? '',
                    'type' => $field->type ?? 'text',
                ];
            }
        }

        return $fields;
    }

    /** Filtros configurados del reporte, por clave. */
    public function configuredFilters(Report $report): array
    {
        return collect($report->settings->filters ?? [])
            ->map(fn ($filter) => (array) $filter)
            ->all();
    }

    /**
     * @param array<int, string> $fields data_key elegidos
     * @param array<string, mixed> $filters clave de filtro => valores
     * @return array{columns: array<int, array{data_key: string, label: string}>, rows: array<int, array<string, mixed>>}
     */
    public function run(Report $report, array $fields, array $filters = [], bool $pivot = false): array
    {
        $configured = $this->configuredFields($report);
        $pivotConfig = $pivot ? ($report->settings->pivot ?? null) : null;
        if ($pivotConfig) {
            // La talla y la cantidad se usan siempre para armar la curva; las demás columnas son las que eligió el usuario
            $fields = array_values(array_unique([...array_diff($fields, [$pivotConfig->key, $pivotConfig->value]), $pivotConfig->key, $pivotConfig->value]));
        }
        $available = $this->configuredFilters($report);

        $model = $report->settings->model ?? null;
        if (!$model || !class_exists($model)) {
            throw new DomainException('El reporte no tiene un modelo válido.');
        }

        $query = $model::query();

        foreach ($report->settings->base_filters ?? [] as $column => $config) {
            $query->where($column, data_get($config, 'operator', '='), data_get($config, 'value'));
        }

        foreach ($filters as $key => $values) {
            if (!isset($available[$key]) || $this->isEmpty($values)) {
                continue;
            }

            $this->applyFilter($query, $available[$key]['data_key'], $values, $available[$key]['type'] ?? 'select');
        }

        // Relaciones necesarias: todo lo que está antes del último punto de cada campo elegido
        $with = collect($fields)
            ->map(fn ($key) => str_contains($key, '.') ? substr($key, 0, strrpos($key, '.')) : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $columns = collect($fields)
            ->filter(fn ($key) => isset($configured[$key]))
            ->map(fn ($key) => ['data_key' => $key, 'label' => $configured[$key]['label']])
            ->values();

        $rows = $query->with($with)->limit(5000)->get()->map(function ($row) use ($columns, $configured) {
            $data = [];
            foreach ($columns as $column) {
                $data[$column['data_key']] = $this->format(data_get($row, $column['data_key']), $configured[$column['data_key']]['type']);
            }

            return $data;
        });

        if ($pivotConfig) {
            return $this->pivot($columns->all(), $rows->all(), $pivotConfig);
        }

        return ['columns' => $columns->all(), 'rows' => $rows->all()];
    }

    /**
     * Pasa los valores de un campo a columnas (ej. una columna por talla con la cantidad como valor, para ver la curva).
     * Las filas con los mismos valores en las demás columnas se juntan sumando la cantidad; al final va el total.
     */
    private function pivot(array $columns, array $rows, object $config): array
    {
        $base = array_values(array_filter($columns, fn ($c) => !in_array($c['data_key'], [$config->key, $config->value], true)));

        $values = [];
        $grouped = [];
        foreach ($rows as $row) {
            $label = (string) ($row[$config->key] ?? '');
            $values[$label] = true;

            $key = json_encode(array_map(fn ($c) => $row[$c['data_key']] ?? '', $base), JSON_UNESCAPED_UNICODE);
            $grouped[$key] ??= array_intersect_key($row, array_flip(array_column($base, 'data_key'))) + ['__values' => []];
            $grouped[$key]['__values'][$label] = ($grouped[$key]['__values'][$label] ?? 0) + (float) ($row[$config->value] ?? 0);
        }

        // Tallas en orden numérico (T35, T36, ... T41)
        $labels = array_keys($values);
        usort($labels, fn ($a, $b) => strnatcasecmp($a, $b));

        $pivotColumns = array_map(fn ($label) => ['data_key' => 'pivot:' . $label, 'label' => $label], $labels);
        $total = ['data_key' => 'pivot:__total', 'label' => $config->total_label ?? 'Total'];

        $out = [];
        foreach ($grouped as $row) {
            $line = array_diff_key($row, ['__values' => 1]);
            $sum = 0;
            foreach ($labels as $label) {
                $amount = $row['__values'][$label] ?? 0;
                $line['pivot:' . $label] = $amount ?: '';
                $sum += $amount;
            }
            $line['pivot:__total'] = $sum;
            $out[] = $line;
        }

        return ['columns' => [...$base, ...$pivotColumns, $total], 'rows' => $out];
    }

    private function isEmpty($values): bool
    {
        if (is_array($values)) {
            return empty(array_filter($values, fn ($v) => $v !== null && $v !== ''));
        }

        return $values === null || $values === '';
    }

    private function applyFilter(Builder $query, string $dataKey, $values, string $type): void
    {
        $parts = explode('.', $dataKey);
        $column = array_pop($parts);
        $relation = implode('.', $parts);

        $condition = function ($q) use ($column, $values, $type) {
            if ($type === 'date_range') {
                $from = $values['from'] ?? null;
                $to = $values['to'] ?? null;

                if ($from && $to) {
                    $q->whereBetween($column, [$from . ' 00:00:00', $to . ' 23:59:59']);
                } elseif ($from) {
                    $q->where($column, '>=', $from . ' 00:00:00');
                } elseif ($to) {
                    $q->where($column, '<=', $to . ' 23:59:59');
                }

                return;
            }

            $q->whereIn($column, (array) $values);
        };

        $relation === '' ? $condition($query) : $query->whereHas($relation, $condition);
    }

    private function format($value, string $type)
    {
        if ($value === null) {
            return '';
        }

        return match ($type) {
            'date' => Carbon::parse($value)->format('Y-m-d'),
            'datetime' => Carbon::parse($value)->format('Y-m-d H:i'),
            'money' => (float) $value,
            default => is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
        };
    }
}

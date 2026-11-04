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
    public function run(Report $report, array $fields, array $filters = []): array
    {
        $configured = $this->configuredFields($report);
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

        return ['columns' => $columns->all(), 'rows' => $rows->all()];
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

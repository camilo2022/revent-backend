<?php

namespace App\Http\Requests\Report;

use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReportGenerateRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors()
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $report = Report::find($this->route('id'));
        $fields = $report ? array_keys(app(ReportService::class)->configuredFields($report)) : [];
        $filters = $report ? array_keys(app(ReportService::class)->configuredFilters($report)) : [];

        return [
            'id' => ['required', 'integer', 'exists:subitems,id,item_id,' . Report::ITEM_ID . ',deleted_at,NULL'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string', Rule::in($fields)],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['nullable'],
            'filters.*.from' => ['nullable', 'date'],
            'filters.*.to' => ['nullable', 'date', 'after_or_equal:filters.*.from'],
        ] + ($filters ? [] : []);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $report = Report::find($this->route('id'));
            if (!$report) {
                return;
            }

            $allowed = array_keys(app(ReportService::class)->configuredFilters($report));
            foreach (array_keys((array) $this->input('filters', [])) as $key) {
                if (!in_array($key, $allowed, true)) {
                    $validator->errors()->add("filters.{$key}", 'El filtro no existe en este reporte.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo es requerido.',
            'integer' => 'El campo debe ser un número entero.',
            'string' => 'El campo debe ser texto.',
            'array' => 'El campo debe ser una lista.',
            'min' => 'Debes elegir al menos un campo.',
            'exists' => 'El valor seleccionado no existe.',
            'in' => 'El campo elegido no pertenece a este reporte.',
            'date' => 'Debe ser una fecha válida.',
            'after_or_equal' => 'Debe ser igual o posterior a la fecha inicial.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador del reporte',
            'fields' => 'Campos',
            'fields.*' => 'Campo',
            'filters' => 'Filtros',
            'filters.*.from' => 'Fecha inicial',
            'filters.*.to' => 'Fecha final',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }
}

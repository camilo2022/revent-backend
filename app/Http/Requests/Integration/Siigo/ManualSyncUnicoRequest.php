<?php

namespace App\Http\Requests\Integration\Siigo;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Sincronización manual con Único desde la pantalla: hoy, ayer o un rango de fechas (máximo 31 días). */
class ManualSyncUnicoRequest extends FormRequest
{
    public const MAX_DAYS = 31;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:today,yesterday,range'],
            'created_start' => ['required_if:mode,range', 'nullable', 'date_format:Y-m-d'],
            'created_end' => ['required_if:mode,range', 'nullable', 'date_format:Y-m-d', 'after_or_equal:created_start'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->input('mode') !== 'range' || $validator->errors()->isNotEmpty()) {
                return;
            }

            $days = Carbon::parse($this->input('created_start'))->diffInDays(Carbon::parse($this->input('created_end'))) + 1;

            if ($days > self::MAX_DAYS) {
                $validator->errors()->add('created_end', 'El rango no puede pasar de ' . self::MAX_DAYS . ' días.');
            }
        }];
    }

    /** Rango consultado en Siigo y su descripción: [inicio, fin, texto]. */
    public function period(): array
    {
        $format = 'Y-m-d H:i:s';

        return match ($this->input('mode')) {
            'yesterday' => [
                now()->subDay()->startOfDay()->format($format),
                now()->subDay()->endOfDay()->format($format),
                'ayer (' . now()->subDay()->format('d/m/Y') . ')',
            ],
            'range' => [
                Carbon::parse($this->input('created_start'))->startOfDay()->format($format),
                Carbon::parse($this->input('created_end'))->endOfDay()->format($format),
                'del ' . Carbon::parse($this->input('created_start'))->format('d/m/Y') . ' al ' . Carbon::parse($this->input('created_end'))->format('d/m/Y'),
            ],
            default => [
                now()->startOfDay()->format($format),
                now()->endOfDay()->format($format),
                'hoy (' . now()->format('d/m/Y') . ')',
            ],
        };
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'required_if' => 'Es obligatorio cuando eliges un rango de fechas.',
            'in' => 'Elige una opción válida.',
            'date_format' => 'Debe ser una fecha válida.',
            'after_or_equal' => 'Debe ser igual o posterior a la fecha inicial.',
        ];
    }

    public function attributes(): array
    {
        return [
            'mode' => 'Periodo',
            'created_start' => 'Fecha inicial',
            'created_end' => 'Fecha final',
        ];
    }
}

<?php

namespace App\Http\Requests\Integration\CustomerSales;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Consulta de clientes por venta: una fecha o un rango (máximo 7 días) y, opcionalmente, un centro de costo. */
class SearchRequest extends FormRequest
{
    public const MAX_DAYS = 7;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'date_start' => ['required', 'date_format:Y-m-d'],
            'date_end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_start'],
            'cost_center' => ['nullable', 'integer'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Sin fecha final se consulta solo el día inicial
            $days = Carbon::parse($this->input('date_start'))->diffInDays(Carbon::parse($this->input('date_end') ?: $this->input('date_start'))) + 1;

            if ($days > self::MAX_DAYS) {
                $validator->errors()->add('date_end', 'El rango no puede pasar de ' . self::MAX_DAYS . ' días.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Pega el token de Siigo para consultar.',
            'date_start.required' => 'Indica la fecha a consultar.',
            'date_format' => 'El campo :attribute no tiene un formato de fecha válido.',
            'date_end.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'integer' => 'El centro de costo no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'token',
            'date_start' => 'fecha inicial',
            'date_end' => 'fecha final',
        ];
    }
}

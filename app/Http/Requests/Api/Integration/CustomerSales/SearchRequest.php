<?php

namespace App\Http\Requests\Api\Integration\CustomerSales;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/**
 * Consulta de clientes por venta: un solo día y, opcionalmente, un centro de costo.
 * El token solo valida que quien consulta sea un usuario real de Siigo; los datos se piden con el token de la API.
 */
class SearchRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', $this->siigoToken()],
            'date' => ['required', 'date_format:Y-m-d'],
            'cost_center' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'token.required' => 'Carga el token de Siigo en Autorización Siigo para consultar.',
            'date.required' => 'Indica la fecha a consultar.',
            'date_format' => 'La fecha no tiene un formato válido (Y-m-d).',
            'cost_center.integer' => 'El centro de costo no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'date' => 'Fecha',
            'cost_center' => 'Centro de costo',
        ];
    }
}

<?php

namespace App\Http\Requests\Employee;

use App\Models\RiskManager;
use App\Models\CompensationFund;
use App\Models\HealthEntity;
use App\Models\PensionFund;
use App\Models\Position;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeStoreRequest extends FormRequest
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
        return [
            'person_id' => ['required', 'numeric', 'exists:people,id,deleted_at,NULL', 'unique:employees,person_id'],
            'position_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . Position::ITEM_ID . ',deleted_at,NULL'],
            'risk_manager_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . RiskManager::ITEM_ID . ',deleted_at,NULL'],
            'health_entity_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . HealthEntity::ITEM_ID . ',deleted_at,NULL'],
            'pension_fund_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . PensionFund::ITEM_ID . ',deleted_at,NULL'],
            'compensation_fund_id' => ['required', 'numeric', 'exists:subitems,id,item_id,' . CompensationFund::ITEM_ID . ',deleted_at,NULL'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after:start_date']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
            'min' => 'Se permite mínimo :min caracteres.',
            'unique' => 'Ya está registrado.',
            'regex' => "Formato inválido:\n- Solo letras.",
            'exists' => 'No hay ningún registro.',
            'date' => 'Debe ser una fecha válida.',
            'date_format' => 'Formato inválido. Permitido :format.',
            'after' => 'Debe ser una fecha posterior a :date.'
        ];
    }

    public function attributes(): array
    {
        return [
            'person_id' => 'Persona',
            'position_id' => 'Cargo',
            'risk_manager_id' => 'Administradora de Riesgos',
            'health_entity_id' => 'Entidad de Salud',
            'pension_fund_id' => 'Fondo de pensión',
            'compensation_fund_id' => 'Caja de compensación',
            'start_date' => 'Fecha inicio',
            'end_date' => 'Fecha fin'
        ];
    }
}

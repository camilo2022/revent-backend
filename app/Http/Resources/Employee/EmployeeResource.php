<?php

namespace App\Http\Resources\Employee;


use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'employee' => [
                'id' => $this->id,
                'person_id' => $this->person_id,
                'operation_center' => $this->operation_center,
                'position_id' => $this->position_id,
                'risk_manager_id' => $this->risk_manager_id,
                'health_entity_id' => $this->health_entity_id,
                'pension_fund_id' => $this->pension_fund_id,
                'compensation_fund_id' => $this->compensation_fund_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'person' => $this->person,
                'position' => $this->position,
                'user' => $this->user,
                'risk_manager' => $this->risk_manager,
                'health_entity' => $this->health_entity,
                'pension_fund' => $this->pension_fund,
                'compensation_fund' => $this->compensation_fund,
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}

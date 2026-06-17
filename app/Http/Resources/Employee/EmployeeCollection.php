<?php

namespace App\Http\Resources\Employee;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EmployeeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'employees' => $this->collection->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'person_id' => $employee->person_id,
                    'operation_center' => $employee->operation_center,
                    'position_id' => $employee->position_id,
                    'risk_manager_id' => $employee->risk_manager_id,
                    'health_entity_id' => $employee->health_entity_id,
                    'pension_fund_id' => $employee->pension_fund_id,
                    'compensation_fund_id' => $employee->compensation_fund_id,
                    'start_date' => $employee->start_date,
                    'end_date' => $employee->end_date,
                    'created_at' => $this->formatDate($employee->created_at),
                    'updated_at' => $this->formatDate($employee->updated_at),
                    'deleted_at' => $this->formatDate($employee->deleted_at),
                    'person' => $employee->person,
                    'position' => $employee->position,
                    'user' => $employee->user,
                    'risk_manager' => $employee->risk_manager,
                    'health_entity' => $employee->health_entity,
                    'pension_fund' => $employee->pension_fund,
                    'compensation_fund' => $employee->compensation_fund,
                ];
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    protected function paginationMeta()
    {
        return [
            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage(),
        ];
    }
}

<?php

namespace App\Http\Resources\Audit;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AuditCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'audits' => $this->collection->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'user_type' => $audit->user_type,
                    'event' => $audit->event,
                    'auditable_type' => $audit->auditable_type,
                    'auditable_name' => $this->getAuditableName(class_basename($audit->auditable_type)),
                    'url' => $audit->url,
                    'ip_address' => $audit->ip_address,
                    'tags' => $audit->tags,
                    'audit_tag' => $this->getTagName($audit->tags ?? null),
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'created_at' => $this->formatDate($audit->created_at),
                    'updated_at' => $this->formatDate($audit->updated_at),
                    'user' => $audit->user,
                    'auditable' => $audit->auditable,
                ];
            }),
            'model_types' => $this->model_types,
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

    protected function getAuditableName($audit_type)
    {
        return $this->model_types[$audit_type] ?? null;
    }

    private $model_types = [
        'Area' => 'Área',
        'BackType' => 'Tipo de Trasero',
        'BloodType' => 'Tipo de Sangre',
        'BootType' => 'Tipo de Bota',
        'Category' => 'Categoría',
        'ClothingLine' => 'Linea',
        'CompensationFund' => 'Caja de Compensación',
        'Employee' => 'Empleado',
        'File' => 'Archivo',
        'GarmentType' => 'Tipo de Prenda',
        'Gender' => 'Género',
        'Group' => 'Grupo',
        'HealthEntity' => 'Entidad de Salud',
        'Module' => 'Módulo',
        'PensionFund' => 'Fondo de Pensión',
        'Permission' => 'Permiso',
        'Person' => 'Persona',
        'Position' => 'Cargo',
        'RiskManager' => 'Administradora de Riesgo',
        'Role' => 'Rol',
        'Silhouette' => 'Silueta',
        'Size' => 'Talla',
        'Subcategory' => 'Subcategoría',
        'Subgroup' => 'Subgrupo',
        'Submodule' => 'Submódulo',
        'Trademark' => 'Marca',
        'User' => 'Usuario',
        'WaistbandType' => 'Tipo de Pretina',
    ];

    protected function getTagName($tag)
    {
        $tag_types = [
            'area' => 'Área',
            'back_type' => 'Tipo de Trasero',
            'blood_type' => 'Tipo de Sangre',
            'boot_type' => 'Tipo de Bota',
            'category' => 'Categoría',
            'clothing_line' => 'Linea',
            'compensation_fund' => 'Caja de Compensación',
            'employee' => 'Empleado',
            'file' => 'Archivo',
            'garment_type' => 'Tipo de Prenda',
            'gender' => 'Género',
            'group' => 'Grupo',
            'health_entity' => 'Entidad de Salud',
            'module' => 'Módulo',
            'pension_fund' => 'Fondo de Pensión',
            'permission' => 'Permiso',
            'person' => 'Persona',
            'position' => 'Cargo',
            'risk_manager' => 'Administradora de Riesgo',
            'role' => 'Rol',
            'silhouette' => 'Silueta',
            'size' => 'Talla',
            'subcategory' => 'Subcategoría',
            'subgroup' => 'Subgrupo',
            'submodule' => 'Submódulo',
            'trademark' => 'Marca',
            'user' => 'Usuario',
            'waistband_type' => 'Tipo de Pretina',
        ];

        return $tag_types[$tag] ?? null;
    }
}

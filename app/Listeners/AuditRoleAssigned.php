<?php

namespace App\Listeners;

use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

class AuditRoleAssigned
{
    public function handle(RoleAttached $event): void
    {
        $userAuth = auth('api')->user();

        $roles = $this->resolveRoles($event->rolesOld ?? $event->rolesOrIds);

        Audit::create([
            'user_type' => $userAuth ? get_class($userAuth) : null,
            'user_id' => $userAuth?->id,
            'auditable_type' => get_class($event->model),
            'auditable_id' => $event->model->id,
            'event' => 'attach',
            'tags' => 'role',
            'old_values' => [
                'roles' =>  $roles
            ],
            'new_values' => [
                'roles' => $event->model->roles()->get(['name', 'title', 'description'])
                    ->makeHidden('pivot'),
            ],
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
            'user_agent' => request()->userAgent(),

        ]);
    }

    private function resolveRoles($rolesOrIds): Collection
    {
        if ($rolesOrIds instanceof Collection) {
            return $rolesOrIds;
        }

        if ($rolesOrIds instanceof Role) {
            return collect([$rolesOrIds]);
        }

        return Role::whereIn('id', (array) $rolesOrIds)
            ->orWhereIn('name', (array) $rolesOrIds)
            ->get();
    }
}

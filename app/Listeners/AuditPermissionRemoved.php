<?php

namespace App\Listeners;

use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

class AuditPermissionRemoved
{
    public function handle(PermissionDetached $event): void
    {
        $userAuth = auth('api')->user();

        $permissions = $this->resolvePermissions($event->permissionsOld ?? $event->permissionsOrIds);

        Audit::create([
            'user_type' => $userAuth ? get_class($userAuth) : null,
            'user_id' => $userAuth?->id,
            'auditable_type' => get_class($event->model),
            'auditable_id' => $event->model->id,
            'event' => 'detach',
            'tags' => 'permission',
            'old_values' => [
                'permissions' =>  $permissions
            ],
            'new_values' => [
                'permissions' => $event->model->permissions()->get(['name', 'title', 'description'])
                    ->makeHidden('pivot'),
            ],
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
            'user_agent' => request()->userAgent(),

        ]);
    }

    private function resolvePermissions($permissionsOrIds): Collection
    {
        if ($permissionsOrIds instanceof Collection) {
            return $permissionsOrIds;
        }

        if ($permissionsOrIds instanceof Permission) {
            return collect([$permissionsOrIds]);
        }

        return Permission::whereIn('id', (array) $permissionsOrIds)
            ->orWhereIn('name', (array) $permissionsOrIds)
            ->get();
    }
}

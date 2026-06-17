<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Spatie\Permission\Events\RoleAttached;
use App\Listeners\AuditRoleAssigned;

class EventServiceProvider extends ServiceProvider
{
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    public function boot(): void
    {
        //
    }
}

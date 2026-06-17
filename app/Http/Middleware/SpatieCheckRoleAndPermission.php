<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\Role;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SpatieCheckRoleAndPermission
{

    use ApiMessage, ApiResponser;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permissions)
    {
        $permissions = explode('|', $permissions);
        $permissionsTitles = Permission::whereIn('name', $permissions)->get()->pluck('title')->join(', ');

        if (!Auth::user()->hasAnyPermission($permissions)) {
            $message = "No está autorizado para realizar esta acción. No cuentas con ninguno de los permisos: {$permissionsTitles}. Contacte al administrador para obtener asistencia o solicitar autorización.";
            return $this->errorResponse(
                [
                    'message' => $message
                ],
                403
            );

            throw new AuthorizationException();
        }

        return $next($request);
    }
}

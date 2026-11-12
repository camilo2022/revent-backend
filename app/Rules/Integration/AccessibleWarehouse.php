<?php

namespace App\Rules\Integration;

use App\Models\Store;
use App\Models\User;
use App\Support\WarehouseOption;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * La bodega (o su tránsito) debe estar entre las tiendas asignadas al usuario autenticado,
 * o el usuario debe tener acceso a todas. Acepta el id de Siigo o el formato "ID-NOMBRE" de los Excel.
 */
class AccessibleWarehouse implements ValidationRule
{
    public function __construct(private ?User $user = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $id = WarehouseOption::id($value);

        if (!is_numeric($id)) {
            $fail('La bodega no es válida.');

            return;
        }

        $user = $this->user ?? Auth::user();

        if ($user && $user->canAccessWarehouse((int) $id)) {
            return;
        }

        $store = Store::where('siigo_warehouse_id', (int) $id)->orWhere('siigo_transit_warehouse_id', (int) $id)->first();
        $name = $store ? $store->name : "con id {$id}";

        $fail("No tienes acceso a la bodega {$name}. Pide que te asignen la tienda.");
    }
}

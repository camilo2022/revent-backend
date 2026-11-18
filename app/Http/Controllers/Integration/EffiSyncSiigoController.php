<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\Effi\EffiWebInventoryComparator;
use Illuminate\Support\Facades\Log;

/** Pantalla de la sincronización de la bodega WEB de Siigo con Effi. Por ahora solo muestra el cuadre: no ajusta. */
class EffiSyncSiigoController extends Controller
{
    public function index()
    {
        return view('integration.effi_sync.index', [
            'siigoWarehouse' => config('effi.siigo_warehouse'),
        ]);
    }

    /** Inventario de los dos lados y sus diferencias. */
    public function compare(EffiWebInventoryComparator $comparator)
    {
        set_time_limit(300);

        try {
            return response()->json(['ok' => true, 'data' => $comparator->compare()]);
        } catch (\Throwable $e) {
            Log::error('Cuadre Siigo/Effi falló', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'No se pudo armar el cuadre: ' . $e->getMessage()], 500);
        }
    }
}

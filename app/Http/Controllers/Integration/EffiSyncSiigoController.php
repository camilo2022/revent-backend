<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Jobs\EffiInventorySyncJob;
use App\Services\Effi\EffiAutoSync;
use App\Services\Effi\EffiClient;
use App\Services\Effi\EffiInventorySyncPlanner;
use App\Services\Effi\EffiWebInventoryComparator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Pantalla de la sincronización de la bodega WEB de Siigo con Effi.
 * compare y plan solo consultan; sync escribe en Effi y solo funciona con EFFI_SYNC_ENABLED=true y la confirmación escrita.
 */
class EffiSyncSiigoController extends Controller
{
    public function index(EffiAutoSync $auto)
    {
        // Mientras hay una sincronización corriendo se muestra su pantalla «en proceso» y no se puede lanzar otra
        if (in_array(Cache::get(EffiInventorySyncJob::STATUS_KEY)['estado'] ?? null, ['planeando', 'ejecutando'], true)) {
            return redirect()->route('siigo.effi_sync_running');
        }

        return view('integration.effi_sync.index', [
            'notifyEmails' => $auto->recipients(),
            'siigoWarehouse' => config('effi.siigo_warehouse'),
            'syncEnabled' => (bool) config('effi.sync_enabled'),
        ]);
    }

    /** Pantalla «en proceso» (mismo estilo que las cargas de traslados y órdenes de compra), con el avance. */
    public function running(EffiAutoSync $auto)
    {
        return view('integration.effi_sync.running', ['email' => implode(', ', $auto->recipients())]);
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

    /** Plan de sincronización (qué se cambiaría en Effi). Solo consulta: no escribe nada. */
    public function plan(EffiInventorySyncPlanner $planner, EffiClient $effi)
    {
        set_time_limit(300);

        try {
            return response()->json(['ok' => true, 'data' => $planner->plan($effi)]);
        } catch (\Throwable $e) {
            Log::error('Plan de sincronización Siigo/Effi falló', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'No se pudo armar el plan: ' . $e->getMessage()], 500);
        }
    }

    /** Ejecuta la sincronización en Effi. Apagada por defecto y con confirmación escrita. */
    public function sync(Request $request)
    {
        if (!config('effi.sync_enabled')) {
            return response()->json(['ok' => false, 'message' => 'La sincronización está desactivada (EFFI_SYNC_ENABLED).'], 403);
        }

        if ($request->input('confirmacion') !== 'SINCRONIZAR') {
            return response()->json(['ok' => false, 'message' => 'Escribe SINCRONIZAR para confirmar.'], 422);
        }

        if (in_array(Cache::get(EffiInventorySyncJob::STATUS_KEY)['estado'] ?? null, ['planeando', 'ejecutando'], true)) {
            return response()->json(['ok' => false, 'message' => 'Ya hay una sincronización en curso.'], 409);
        }

        Cache::put(EffiInventorySyncJob::STATUS_KEY, ['estado' => 'planeando', 'fase' => 'En segundo plano (cola «' . config('effi.queue') . '»)'], 86400);
        // Lo que se eligió en la vista (categorías de los artículos nuevos y qué hacer con los duplicados)
        $decisions = [
            'modelos' => array_filter((array) $request->input('decisiones.modelos', []), fn ($v) => is_array($v) && in_array($v['accion'] ?? null, ['existente', 'crear', 'ninguna'], true)),
            'duplicados' => array_filter((array) $request->input('decisiones.duplicados', []), fn ($v) => is_array($v) && (isset($v['inhabilitar']) || !empty($v['ninguno']))),
        ];
        // Prueba con un solo código: ignora el resto del plan
        $only = is_string($request->input('solo')) && $request->input('solo') !== '' ? mb_substr($request->input('solo'), 0, 60) : null;

        EffiInventorySyncJob::dispatch($decisions, $only);

        return response()->json(['ok' => true, 'correos' => app(EffiAutoSync::class)->recipients()]);
    }

    /** Avance de la última sincronización. */
    public function status()
    {
        return response()->json(['ok' => true, 'data' => Cache::get(EffiInventorySyncJob::STATUS_KEY)]);
    }
}

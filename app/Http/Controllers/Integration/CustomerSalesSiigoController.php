<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\CustomerSales\SearchRequest;
use App\Services\Siigo\SiigoCustomerSalesService;
use Illuminate\Http\Request;

/**
 * Consulta de clientes que compraron en una fecha (o rango) y en un centro de costo, con lo que compraron.
 * Siempre usa el token de la API de Siigo que la persona pega en pantalla, nunca el generado con las credenciales del .env.
 */
class CustomerSalesSiigoController extends Controller
{
    public function __construct(private SiigoCustomerSalesService $sales)
    {
    }

    public function customer_sales()
    {
        return view('integration.customer_sales.index', [
            'costCenters' => [],
            'customers' => null,
        ]);
    }

    /** Centros de costo para el filtro, cargados con el token pegado. */
    public function customer_sales_cost_centers(Request $request)
    {
        $request->validate(['token' => ['required', 'string']]);

        try {
            return response()->json(['success' => true, 'cost_centers' => $this->sales->costCenters(trim($request->input('token')))]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Token inválido o vencido.'], 401);
        }
    }

    public function customer_sales_search(SearchRequest $request)
    {
        // La consulta recorre todas las facturas del rango: puede tardar
        set_time_limit(600);

        $token = trim($request->input('token'));
        $dateEnd = $request->input('date_end') ?: $request->input('date_start');
        $costCenter = $request->filled('cost_center') ? (int) $request->input('cost_center') : null;

        try {
            $costCenters = $this->sales->costCenters($token);
            $customers = $this->sales->search($token, $request->input('date_start'), $dateEnd, $costCenter);
        } catch (\Throwable $e) {
            // El token no se guarda en la sesión
            return back()->withInput($request->except('token'))->withErrors(['token' => 'No se pudo consultar Siigo (revisa que el token sea válido y no haya vencido).']);
        }

        return view('integration.customer_sales.index', [
            'costCenters' => $costCenters,
            'customers' => $customers,
        ]);
    }
}

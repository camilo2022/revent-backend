<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\CustomerSales\SearchRequest;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoCustomerSalesService;
use App\Services\Siigo\SiigoUserService;

/**
 * Consulta de clientes que compraron en una fecha y en un centro de costo, con lo que compraron.
 * El token que pega la persona (el mismo de las otras pantallas) solo valida que sea un usuario real de Siigo;
 * los datos se consultan con el token normal de la API, generado con las credenciales del .env.
 */
class CustomerSalesSiigoController extends Controller
{
    public function __construct(
        private SiigoCustomerSalesService $sales,
        private SiigoClient $client,
        private SiigoUserService $siigoUsers
    )
    {
    }

    public function customer_sales()
    {
        $view = view('integration.customer_sales.index', [
            'costCenters' => $this->costCenters(),
            'customers' => null,
        ]);

        return $this->costCentersFailed ? $view->withErrors(['cost_center' => 'No se pudieron cargar los centros de costo.']) : $view;
    }

    public function customer_sales_search(SearchRequest $request)
    {
        // El token debe ser de un usuario válido de Siigo (si no, nadie sin acceso consulta clientes)
        $user = $this->siigoUsers->fromToken(trim($request->input('token')));

        if (!$user['success']) {
            // El token no se guarda en la sesión
            return back()->withInput($request->except('token'))->withErrors(['token' => 'El token no es válido o ya venció. Cópialo de nuevo desde Siigo.']);
        }

        // La consulta recorre todas las facturas del día: puede tardar
        set_time_limit(600);

        $date = $request->input('date');
        $costCenter = $request->filled('cost_center') ? (int) $request->input('cost_center') : null;

        try {
            $customers = $this->sales->search($this->client->token(), $date, $date, $costCenter);
        } catch (\Throwable $e) {
            return back()->withInput($request->except('token'))->withErrors(['date' => 'No se pudo consultar Siigo. Inténtalo de nuevo en unos minutos.']);
        }

        return view('integration.customer_sales.index', [
            'costCenters' => $this->costCenters(),
            'customers' => $customers,
        ]);
    }

    private bool $costCentersFailed = false;

    // Centros de costo con el token del .env; si Siigo falla, el filtro queda vacío y se avisa
    private function costCenters(): array
    {
        try {
            return $this->sales->costCenters($this->client->token());
        } catch (\Throwable $e) {
            $this->costCentersFailed = true;

            return [];
        }
    }
}

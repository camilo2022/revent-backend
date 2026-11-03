<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\CustomerSales\SearchRequest;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoCustomerSalesService;

/** Consulta de clientes que compraron en una fecha (o rango) y en un centro de costo, con lo que compraron. */
class CustomerSalesSiigoController extends Controller
{
    public function __construct(private SiigoCustomerSalesService $sales, private SiigoClient $client)
    {
    }

    public function customer_sales()
    {
        return view('integration.customer_sales.index', [
            'costCenters' => $this->sales->costCenters($this->client->token()),
            'customers' => null,
        ]);
    }

    public function customer_sales_search(SearchRequest $request)
    {
        // La consulta recorre todas las facturas del rango: puede tardar
        set_time_limit(600);

        $token = $this->client->token();
        $dateEnd = $request->input('date_end') ?: $request->input('date_start');
        $costCenter = $request->filled('cost_center') ? (int) $request->input('cost_center') : null;

        try {
            $customers = $this->sales->search($token, $request->input('date_start'), $dateEnd, $costCenter);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['date_start' => 'No se pudo consultar Siigo: ' . $e->getMessage()]);
        }

        return view('integration.customer_sales.index', [
            'costCenters' => $this->sales->costCenters($token),
            'customers' => $customers,
            'dateEnd' => $dateEnd,
        ]);
    }
}

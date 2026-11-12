<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\CustomerSales\CostCentersRequest;
use App\Http\Requests\Api\Integration\CustomerSales\SearchRequest;
use App\Http\Resources\Api\Integration\CustomerSales\CostCenterCollection;
use App\Http\Resources\Api\Integration\CustomerSales\CustomerSaleCollection;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoCustomerSalesService;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;

/**
 * Clientes que compraron en una fecha y en un centro de costo, con lo que compraron. El token del usuario
 * (validado en el request) solo comprueba que sea un usuario de Siigo; los datos se consultan con el token
 * de la API pública generado con las credenciales del .env, igual que la pantalla de Integration.
 */
class CustomerSalesController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    public function __construct(private SiigoCustomerSalesService $sales, private SiigoClient $client)
    {
    }

    public function costCenters(CostCentersRequest $request)
    {
        try {
            try {
                $costCenters = $this->sales->costCenters($this->client->token());
            } catch (\Throwable $e) {
                Log::warning('Clientes por venta: no se pudieron cargar los centros de costo.', ['exception' => $e->getMessage()]);

                throw new \DomainException('No se pudieron cargar los centros de costo.');
            }

            return $this->successResponse(new CostCenterCollection($costCenters), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function search(SearchRequest $request)
    {
        try {
            // La consulta recorre todas las facturas del día: puede tardar
            set_time_limit(600);

            $date = $request->input('date');
            $costCenter = $request->filled('cost_center') ? (int) $request->input('cost_center') : null;

            try {
                $customers = $this->sales->search($this->client->token(), $date, $date, $costCenter);
            } catch (\Throwable $e) {
                Log::warning('Clientes por venta: no se pudo consultar Siigo.', ['exception' => $e->getMessage()]);

                throw new \DomainException('No se pudo consultar Siigo. Inténtalo de nuevo en unos minutos.');
            }

            return $this->successResponse(new CustomerSaleCollection($customers), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

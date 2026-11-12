<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\AccountPayable\AdvanceStoreRequest;
use App\Http\Requests\Api\Integration\AccountPayable\ConciliationStoreRequest;
use App\Http\Requests\Api\Integration\AccountPayable\DocumentsRequest;
use App\Http\Requests\Api\Integration\AccountPayable\PaymentHtmlRequest;
use App\Http\Requests\Api\Integration\AccountPayable\PaymentsRequest;
use App\Http\Requests\Api\Integration\AccountPayable\PaymentsWeeklyRequest;
use App\Http\Requests\Api\Integration\AccountPayable\PaymentStoreRequest;
use App\Http\Requests\Api\Integration\AccountPayable\ProvidersRequest;
use App\Http\Requests\Api\Integration\AccountPayable\ProvidersSyncRequest;
use App\Http\Requests\Api\Integration\AccountPayable\SettingsRequest;
use App\Http\Resources\Api\Integration\AccountPayable\AccountPayableSettingsResource;
use App\Http\Resources\Api\Integration\AccountPayable\PaymentHtmlResource;
use App\Http\Resources\Api\Integration\AccountPayable\PaymentsByMonthCollection;
use App\Http\Resources\Api\Integration\AccountPayable\ProviderCollection;
use App\Http\Resources\Api\Integration\AccountPayable\ProviderDocumentCollection;
use App\Http\Resources\Api\Integration\AccountPayable\VoucherResource;
use App\Http\Resources\Api\Integration\AccountPayable\WeeklyPaymentsResource;
use App\Services\Siigo\AccountPayable\AccountPayableException;
use App\Services\Siigo\AccountPayable\SiigoAccountPayableService;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Cuentas por pagar (Siigo). Cada ruta hace una sola acción y tiene su propio permiso, así se puede dar solo la
 * consulta sin dar los pagos, anticipos ni conciliaciones. La lógica es la de SiigoAccountPayableService (la misma
 * que usa Integration\AccountPayableSiigoController); aquí solo se valida, se autoriza y se da forma a la respuesta.
 */
class AccountPayableController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    public function __construct(private SiigoAccountPayableService $accountPayable)
    {
    }

    public function settings(SettingsRequest $request)
    {
        try {
            return $this->successResponse(new AccountPayableSettingsResource($this->accountPayable->screenData()), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Proveedores con saldo; los datos de cada proveedor salen del caché que ya usa Integration. */
    public function providers(ProvidersRequest $request)
    {
        try {
            return $this->successResponse(new ProviderCollection($this->accountPayable->providers(false)), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Igual que providers, pero vuelve a leer cada proveedor en Siigo (antes era `?sync=1`). */
    public function providersSync(ProvidersSyncRequest $request)
    {
        try {
            return $this->successResponse(new ProviderCollection($this->accountPayable->providers(true)), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function documents(DocumentsRequest $request, int $accountId)
    {
        try {
            $documents = $this->accountPayable->providerDocuments(
                $request->input('MsThirdPartyID'),
                $accountId,
                $request->boolean('all_data')
            );

            return $this->successResponse(new ProviderDocumentCollection($documents), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function payments(PaymentsRequest $request, int $accountId)
    {
        try {
            return $this->successResponse(new PaymentsByMonthCollection($this->accountPayable->paymentsByMonth($accountId)), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function paymentsWeekly(PaymentsWeeklyRequest $request)
    {
        try {
            ini_set('memory_limit', '-1');
            set_time_limit(0);

            Carbon::setLocale('es');

            // Sin fechas: todo el año hasta hoy
            $from = $request->filled('date_from')
                ? Carbon::createFromFormat('Y-m-d', $request->input('date_from'))->startOfDay()
                : Carbon::now()->startOfYear();

            $to = $request->filled('date_to')
                ? Carbon::createFromFormat('Y-m-d', $request->input('date_to'))->endOfDay()
                : Carbon::now();

            $weekly = [
                'desde' => $from->format('Y-m-d'),
                'hasta' => $to->format('Y-m-d'),
                'weeks' => $this->accountPayable->weeklyPayments($from, $to),
            ];

            return $this->successResponse(new WeeklyPaymentsResource($weekly), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function paymentHtml(PaymentHtmlRequest $request, int $acEntryId)
    {
        try {
            return $this->successResponse(new PaymentHtmlResource($this->accountPayable->paymentHtml($acEntryId)), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function paymentStore(PaymentStoreRequest $request)
    {
        try {
            $result = $this->guard(fn () => $this->accountPayable->registerPayment(
                $request->input('proveedor'),
                $request->input('documentos'),
                $this->voucherData($request),
                $request->file('comprobante')
            ));

            return $this->successResponse(new VoucherResource($result), 'Recibo de pago registrado correctamente.', 201);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function advanceStore(AdvanceStoreRequest $request)
    {
        try {
            $result = $this->guard(fn () => $this->accountPayable->registerAdvance(
                $request->input('proveedor'),
                $this->voucherData($request),
                $request->file('comprobante')
            ));

            return $this->successResponse(new VoucherResource($result), 'Recibo de anticipo registrado correctamente.', 201);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function conciliationStore(ConciliationStoreRequest $request)
    {
        try {
            $result = $this->guard(fn () => $this->accountPayable->registerConciliation(
                $request->input('proveedor'),
                $request->input('documentos'),
                $request->input('recibos'),
                [
                    'fecha' => $request->input('fecha'),
                    'tipo' => $request->input('tipo'),
                    'observaciones' => $request->input('observaciones'),
                ]
            ));

            return $this->successResponse(new VoucherResource($result), 'Cruce contable registrado correctamente.', 201);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    /** Campos comunes de un recibo de pago o anticipo. */
    private function voucherData(Request $request): array
    {
        return [
            'fecha' => $request->input('fecha'),
            'tipo' => $request->input('tipo'),
            'accion' => $request->input('accion'),
            'origen' => $request->input('origen'),
            'valor' => $request->input('valor'),
            'observaciones' => $request->input('observaciones'),
        ];
    }

    /** Los errores de negocio del servicio salen como 422 con su mensaje, igual que el resto de la API. */
    private function guard(callable $action): array
    {
        try {
            return $action();
        } catch (AccountPayableException $e) {
            throw new \DomainException($e->getMessage(), 0, $e);
        }
    }
}

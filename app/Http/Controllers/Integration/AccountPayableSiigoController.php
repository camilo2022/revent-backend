<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Integration\Concerns\SendsAccessLink;
use App\Http\Requests\Integration\AccountPayable\AdvanceRequest;
use App\Http\Requests\Integration\AccountPayable\ConciliationRequest;
use App\Http\Requests\Integration\AccountPayable\DocumentsRequest;
use App\Http\Requests\Integration\AccountPayable\PaymentRequest;
use App\Http\Requests\Integration\AccountPayable\PaymentsWeeklyRequest;
use App\Http\Requests\Integration\AccountPayable\ProvidersRequest;
use App\Http\Requests\Integration\AccountPayable\SendAccessLinkRequest;
use App\Mail\AccountPayableAccessLinkMail;
use App\Services\Siigo\AccountPayable\AccountPayableException;
use App\Services\Siigo\AccountPayable\SiigoAccountPayableService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountPayableSiigoController extends Controller
{
    use SendsAccessLink;

    public function __construct(private SiigoAccountPayableService $accountPayable)
    {
    }

    public function account_payable_access()
    {
        return view('integration.account_payable.access');
    }

    public function account_payable_send_access_link(SendAccessLinkRequest $request)
    {
        $allowed = config('siigo.access.account_payable');

        return $this->sendAccessLink(
            $request,
            array_keys($allowed),
            'siigo.account_payable',
            'cuentas por pagar',
            fn (string $url) => new AccountPayableAccessLinkMail($url),
            fn (string $email) => ['required_token' => $allowed[$email] ?? true]
        );
    }

    public function account_payable()
    {
        return view('integration.account_payable.index', $this->accountPayable->screenData());
    }

    public function account_payable_documents(DocumentsRequest $request, int $accountId)
    {
        if ($request->input('type') == 'payment') {
            return response()->json([
                'payments' => $this->accountPayable->paymentsByMonth($accountId),
            ]);
        }

        return response()->json([
            'documents' => $this->accountPayable->providerDocuments(
                $request->input('MsThirdPartyID'),
                $accountId,
                $request->boolean('all_data')
            ),
        ]);
    }

    public function accounts_payment(PaymentRequest $request)
    {
        $documentos = json_decode($request->input('documentos'), true);

        if (!is_array($documentos) || empty($documentos)) {
            return response()->json([
                'message' => 'Debes seleccionar al menos un documento a pagar.',
            ], 422);
        }

        try {
            $result = $this->accountPayable->registerPayment(
                json_decode($request->proveedor, true),
                $documentos,
                $this->voucherData($request),
                $request->file('comprobante')
            );
        } catch (AccountPayableException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }

        return $this->registeredResponse('Recibo de pago registrado correctamente.', $result);
    }

    public function accounts_advance(AdvanceRequest $request)
    {
        $result = $this->accountPayable->registerAdvance(
            json_decode($request->proveedor, true),
            $this->voucherData($request),
            $request->file('comprobante')
        );

        return $this->registeredResponse('Recibo de anticipo registrado correctamente.', $result);
    }

    public function accounts_conciliation(ConciliationRequest $request)
    {
        $result = $this->accountPayable->registerConciliation(
            json_decode($request->proveedor, true),
            json_decode($request->documentos, true),
            json_decode($request->recibos, true),
            [
                'fecha' => $request->input('fecha'),
                'tipo' => $request->input('tipo'),
                'observaciones' => $request->input('observaciones'),
            ]
        );

        return $this->registeredResponse('Cruce contable registrado correctamente.', $result);
    }

    public function accounts_payable_providers(ProvidersRequest $request)
    {
        return response()->json([
            'providers' => $this->accountPayable->providers($request->boolean('sync')),
        ]);
    }

    public function account_payable_payments_weekly(PaymentsWeeklyRequest $request)
    {
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

        return response()->json([
            'desde' => $from->format('Y-m-d'),
            'hasta' => $to->format('Y-m-d'),
            'weeks' => $this->accountPayable->weeklyPayments($from, $to),
        ]);
    }

    public function payment_html(int $acEntryId): string
    {
        return $this->accountPayable->paymentHtml($acEntryId);
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

    private function registeredResponse(string $message, array $result): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'voucher_id' => $result['voucher_id'],
            'voucher' => $result['voucher'],
        ]);
    }
}

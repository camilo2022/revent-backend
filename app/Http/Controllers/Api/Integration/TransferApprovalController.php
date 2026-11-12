<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\TransferApproval\ConfirmRequest;
use App\Http\Requests\Api\Integration\TransferApproval\SearchRequest;
use App\Http\Resources\Api\Integration\TransferApproval\TransferApprovalQueuedResource;
use App\Http\Resources\Api\Integration\TransferApproval\TransferNoteResource;
use App\Jobs\Integration\ImportMasiveTransferSiigoJob;
use App\Services\Integration\TransferApprovalPreparer;
use App\Support\Integration\LegacyCacheKeys;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Cache;

/**
 * Aprobación (recepción) de notas de traslado hacia una bodega de tránsito.
 * Al confirmar arma lo mismo que trae el Excel de aprobación y lo entrega a ImportMasiveTransferSiigoJob (sin
 * cambios), que crea el traslado en Siigo y envía los correos. El usuario del ERP solo puede aprobar notas de
 * bodegas de tránsito de las tiendas que tiene asignadas; el token de Siigo se usa solo para ejecutar la acción.
 *
 * Copiado de Integration\TransferApprovalSiigoController::transfer_approval_confirm (commit b8694f73).
 */
class TransferApprovalController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    public function __construct(private TransferApprovalPreparer $preparer)
    {
    }

    public function search(SearchRequest $request)
    {
        try {
            $note = $this->preparer->prepare($request->user(), $request->input('token'), $request->input('query'));

            return $this->successResponse(new TransferNoteResource($note), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function confirm(ConfirmRequest $request)
    {
        try {
            $note = $this->preparer->prepare($request->user(), $request->input('token'), $request->input('query'));

            $chosen = collect($request->input('items'))->mapWithKeys(fn ($item) => [$item['code'] => (int) $item['quantity']]);
            $details = [];

            foreach ($chosen as $code => $quantity) {
                $item = collect($note['items'])->firstWhere('code', $code);

                if (!$item) {
                    throw new \DomainException("El producto {$code} no pertenece a la nota de traslado.");
                }

                if ($quantity > $item['max']) {
                    throw new \DomainException("{$code}: la cantidad ({$quantity}) supera el máximo permitido ({$item['max']}).");
                }

                if ($quantity > 0) {
                    $details[] = [
                        'codigo' => $code,
                        'descripcion' => $item['description'],
                        'bodega_salida' => $note['transit']['id'],
                        'bodega_entrada' => $note['destination']['id'],
                        'cantidad' => $quantity,
                    ];
                }
            }

            if (empty($details)) {
                throw new \DomainException('Debes aprobar al menos una unidad.');
            }

            // Mismo bloqueo que la pantalla web: evita crear el traslado dos veces desde cualquiera de los dos lados
            if (!Cache::add(LegacyCacheKeys::transferApprovalLock($note['code']), true, LegacyCacheKeys::TRANSFER_APPROVAL_LOCK_SECONDS)) {
                return $this->errorResponse([
                    'message' => "La nota {$note['code']} se acaba de confirmar. Espera un par de minutos antes de repetirla.",
                    'error' => 'TooManyRequests',
                ], 429);
            }

            // Mismas hojas que trae el Excel de aprobación de traslado en tránsito.
            $transfer = collect([
                'traslado' => collect([collect([
                    'token' => $request->input('token'),
                    'fecha' => now()->format('Y-m-d'),
                    'validar_disponible' => 'SI',
                    'tipo' => 'DIRECTO',
                    'observacion' => "APROBACION DE TRASLADO DE TRANSITO: {$note['code']}",
                ])]),
                'traslado_detalles' => collect($details),
            ]);

            $email = $this->preparer->warehouseEmail($note['transit']['id']);

            ImportMasiveTransferSiigoJob::dispatch($transfer, $email, $request->user()->id);

            return $this->successResponse(new TransferApprovalQueuedResource([
                'code' => $note['code'],
                'email' => $email,
                'units' => (int) $chosen->sum(),
            ]), 'La aprobación fue recibida. El traslado se está creando y el resultado llegará a los correos de las bodegas.', 202);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\TransferApproval\ConfirmRequest;
use App\Http\Requests\Integration\TransferApproval\SearchRequest;
use App\Jobs\ImportMasiveTransferSiigoJob;
use App\Services\Siigo\SiigoTransferApprovalService;
use App\Services\Siigo\SiigoUserService;
use Illuminate\Support\Facades\Cache;

/**
 * Aprobación (recepción) de notas de traslado hacia una bodega de tránsito desde una vista.
 * Al confirmar arma lo mismo que trae el Excel de aprobación y lo entrega a ImportMasiveTransferSiigoJob,
 * que valida, crea el traslado y envía los correos de salida e ingreso a las bodegas igual que la carga masiva.
 * El correo de resultado (éxito o error) del job va al correo asociado a la bodega de tránsito, en vez de uno escrito a mano.
 */
class TransferApprovalSiigoController extends Controller
{
    /** Segundos que se bloquea una NT tras confirmar, para no crear el traslado dos veces con un doble clic. */
    private const LOCK_SECONDS = 120;

    public function __construct(
        private SiigoTransferApprovalService $approval,
        private SiigoUserService $siigoUsers
    ) {
    }

    public function transfer_approval()
    {
        return view('integration.transfer_approval.index');
    }

    public function transfer_approval_search(SearchRequest $request)
    {
        $result = $this->prepare($request->input('token'), $request->input('query'));

        if (isset($result['response'])) {
            return $result['response'];
        }

        return response()->json(['success' => true, 'note' => $result['note']]);
    }

    public function transfer_approval_confirm(ConfirmRequest $request)
    {
        $result = $this->prepare($request->input('token'), $request->input('query'));

        if (isset($result['response'])) {
            return $result['response'];
        }

        $note = $result['note'];
        $chosen = collect($request->input('items'))->mapWithKeys(fn ($item) => [$item['code'] => (int) $item['quantity']]);
        $details = [];

        foreach ($chosen as $code => $quantity) {
            $item = collect($note['items'])->firstWhere('code', $code);

            if (!$item) {
                return $this->error("El producto {$code} no pertenece a la nota de traslado.");
            }

            if ($quantity > $item['max']) {
                return $this->error("{$code}: la cantidad ({$quantity}) supera el máximo permitido ({$item['max']}).");
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
            return $this->error('Debes aprobar al menos una unidad.');
        }

        if (!Cache::add("transfer-approval:{$note['code']}", true, self::LOCK_SECONDS)) {
            return $this->error("La nota {$note['code']} se acaba de confirmar. Espera un par de minutos antes de repetirla.", 429);
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

        ImportMasiveTransferSiigoJob::dispatch($transfer, $this->warehouseEmail($note['transit']['id']));

        return response()->json([
            'success' => true,
            'message' => 'La aprobación fue recibida. El traslado se está creando y el resultado llegará a los correos de las bodegas.',
        ]);
    }

    /**
     * Valida el token, lee la NT, comprueba el acceso a la bodega de tránsito y calcula lo máximo
     * a aprobar por producto (menor entre lo que dice la NT y lo disponible hoy en tránsito).
     *
     * @return array{note?: array, response?: \Illuminate\Http\JsonResponse}
     */
    private function prepare(string $token, string $query): array
    {
        $user = $this->siigoUsers->fromToken($token);

        if (!$user['success']) {
            return ['response' => $this->error($user['message'], 401)];
        }

        $found = $this->approval->find($query);

        if (!$found['note']) {
            return ['response' => $this->error($found['error'], 404)];
        }

        $note = $found['note'];
        $transit = $this->approval->warehouses()['TRANSITO'][$note['transit']['id']];

        if (!in_array($user['data']['id'], $transit['users'] ?? [])) {
            return ['response' => $this->error("No tiene acceso a la bodega {$note['transit']['name']}.", 403)];
        }

        $available = $this->approval->availability($token, array_column($note['items'], 'code'), (int) $note['transit']['id']);

        $note['items'] = array_map(function ($item) use ($available) {
            $item['available'] = $available[$item['code']] ?? 0;
            $item['max'] = min($item['requested'], $item['available']);

            return $item;
        }, $note['items']);

        return ['note' => $note];
    }

    /**
     * Correo asociado a la bodega (el job recibe uno solo), completado con el dominio igual que en el job.
     * Prefiere el del dominio de la empresa; si la bodega no tiene correos, operaciones.
     */
    private function warehouseEmail(int|string $warehouseId): string
    {
        $domain = config('siigo.mail.domain');

        $emails = array_map(
            fn (string $email) => str_contains($email, '@') ? $email : "{$email}@{$domain}",
            $this->approval->warehouses()['TRANSITO'][$warehouseId]['emails'] ?? []
        );

        return collect($emails)->first(fn (string $email) => str_ends_with($email, "@{$domain}"))
            ?? $emails[0]
            ?? config('siigo.mail.operations');
    }

    private function error(string $message, int $status = 422)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}

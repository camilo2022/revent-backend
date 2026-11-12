<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Api\Integration\Concerns\RespondsWithFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\MasivePurchaseOrders\FormatRequest;
use App\Http\Requests\Api\Integration\MasivePurchaseOrders\TypesRequest;
use App\Http\Requests\Api\Integration\MasivePurchaseOrders\UploadRequest;
use App\Http\Resources\Api\Integration\MasivePurchaseOrders\MasivePurchaseOrderQueuedResource;
use App\Http\Resources\Api\Integration\MasivePurchaseOrders\PurchaseOrderTypeResource;
use App\Jobs\ImportMasivePurchaseOrderSiigoJob;
use App\Services\Integration\MasivePurchaseOrderFormatService;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/** Órdenes de compra masivas: tipos, formato de Excel (con la cookie de Siigo) y carga que despacha el job. */
class MasivePurchaseOrderController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure, RespondsWithFile;

    public function __construct(private MasivePurchaseOrderFormatService $formatter)
    {
    }

    public function types(TypesRequest $request)
    {
        try {
            return $this->successResponse(new PurchaseOrderTypeResource($this->formatter->types()), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function format(FormatRequest $request)
    {
        try {
            [$export, $filename] = $this->formatter->build($request->siigoJwt(), $request->input('cookie'), $request->input('purchase_order_type_id'));

            return $this->downloadExcel($export, $filename);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function upload(UploadRequest $request)
    {
        try {
            $order = $request->order();
            $email = $request->input('email');
            $referencia = $request->input('referencia');

            ImportMasivePurchaseOrderSiigoJob::dispatch($order, $email, $referencia);

            return $this->successResponse(
                new MasivePurchaseOrderQueuedResource([
                    'email' => $email,
                    'referencia' => $referencia,
                    'lines' => $order['orden_compra_detalles']->count(),
                ]),
                'Las órdenes de compra se están procesando. El resultado llegará al correo indicado.',
                202
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

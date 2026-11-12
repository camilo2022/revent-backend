<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\PurchaseOrderReception\ConfirmRequest;
use App\Http\Requests\Api\Integration\PurchaseOrderReception\SearchRequest;
use App\Http\Resources\Api\Integration\PurchaseOrderReception\PurchaseOrderReceptionResource;
use App\Http\Resources\Api\Integration\PurchaseOrderReception\ReceptionConfirmedResource;
use App\Mail\InvoicePurchaseOrderConfirmedSiigoMail;
use App\Services\Integration\PurchaseOrderReceptionService;
use App\Support\Integration\ReceptionMailCopy;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Recepción de órdenes de compra: buscar la orden y confirmar la recepción con fotos de evidencia. */
class PurchaseOrderReceptionController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    private const DISK = 'public';
    private const BASE_PATH = 'evidences';

    public function __construct(private PurchaseOrderReceptionService $reception)
    {
    }

    public function search(SearchRequest $request)
    {
        try {
            $order = $this->reception->find((string) $request->input('query'), Auth::user());

            return $this->successResponse(new PurchaseOrderReceptionResource(['order' => $order]), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function confirm(ConfirmRequest $request)
    {
        try {
            $imagenes = [];

            foreach ($request->file('imagenes', []) as $imagen) {
                $filename = (string) Str::uuid() . '.' . $imagen->getClientOriginalExtension();

                Storage::disk(self::DISK)->putFileAs(self::BASE_PATH, $imagen, $filename);

                $imagenes[] = Storage::disk(self::DISK)->url(self::BASE_PATH . "/{$filename}");
            }

            // Mismo formato de usuario que entrega SiigoUserService::fromToken, que es el que usa el correo
            $usuario = ['success' => true, 'data' => $request->siigoUser(), 'message' => ''];
            $to = [config('siigo.mail.purchase_reception')];

            // Con copia al correo de la tienda que recibe, para que sus respuestas le lleguen
            $cc = ReceptionMailCopy::forUser(Auth::user(), (int) $usuario['data']['id'], $to);

            Mail::to($to)->cc($cc)->send(new InvoicePurchaseOrderConfirmedSiigoMail($request->receivingData(), $usuario, $imagenes));

            return $this->successResponse(
                new ReceptionConfirmedResource(['images' => count($imagenes), 'sent_to' => $to, 'cc' => $cc]),
                'La recepción fue registrada y el correo fue enviado correctamente.',
                200
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

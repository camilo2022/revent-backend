<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Api\Integration\Concerns\RespondsWithFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\SearchDocument\PurchaseOrderPdfRequest;
use App\Http\Requests\Api\Integration\SearchDocument\PurchaseOrderRequest;
use App\Http\Requests\Api\Integration\SearchDocument\TransferNotePdfRequest;
use App\Http\Requests\Api\Integration\SearchDocument\TransferNoteRequest;
use App\Http\Requests\Api\Integration\SearchDocument\TypesRequest;
use App\Http\Resources\Api\Integration\SearchDocument\DocumentResource;
use App\Http\Resources\Api\Integration\SearchDocument\DocumentTypeCollection;
use App\Services\Siigo\SiigoClient;
use App\Services\SiigoPurchaseOCPdfService;
use App\Services\SiigoTransferNTPdfService;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/**
 * Búsqueda de documentos de Siigo y su PDF. Una ruta por tipo: la nota de traslado (23) usa el token JWT del
 * usuario y la orden de compra (19) la cookie completa del monolito. La lógica la ponen los mismos servicios que usa
 * Integration\SearchDocumentSiigoController; las credenciales se validan en el FormRequest (422, nunca 401).
 *
 * Copiado de Integration\SearchDocumentSiigoController::DOCUMENT_TYPES y ::search_document_search (commit b8694f73).
 */
class SearchDocumentController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure, RespondsWithFile;

    private const TYPES = [
        SiigoPurchaseOCPdfService::PURCHASE_ORDER => [
            'name' => 'ORDEN DE COMPRA',
            'credential' => 'cookie',
            'hint' => 'Cookie completa de Siigo (TKNSGRDDREVENTCALZADOSAS=...; .ASPXAUTH=...).',
        ],
        SiigoTransferNTPdfService::TRANSFER_NOTE => [
            'name' => 'NOTA DE TRASLADO',
            'credential' => 'token',
            'hint' => 'Solo el token JWT (empieza por eyJ...).',
        ],
    ];

    public function __construct(
        private SiigoTransferNTPdfService $transferNote,
        private SiigoPurchaseOCPdfService $purchaseOrder,
        private SiigoClient $client
    ) {
    }

    public function types(TypesRequest $request)
    {
        try {
            return $this->successResponse(new DocumentTypeCollection(self::TYPES), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function transferNote(TransferNoteRequest $request)
    {
        try {
            $siigoToken = $this->client->token();

            $document = $this->transferNote->find_document($siigoToken, trim($request->input('query')), SiigoTransferNTPdfService::TRANSFER_NOTE);

            if ($document) {
                $document['Items'] = $this->transferNote->parse_transfer_items(
                    $this->transferNote->transfer_note_html($siigoToken, $document['ACEntryID'])
                );
            }

            return $this->successResponse(new DocumentResource($document), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function purchaseOrder(PurchaseOrderRequest $request)
    {
        try {
            $siigoToken = $this->client->token();

            $document = $this->transferNote->find_document($siigoToken, trim($request->input('query')), SiigoPurchaseOCPdfService::PURCHASE_ORDER);

            if ($document) {
                // El monolito se consulta con la cookie completa que mandó el usuario
                $document['Items'] = $this->purchaseOrder->parse_purchase_items(
                    $this->purchaseOrder->purchase_order_html($request->input('cookie'), $document)
                );
            }

            return $this->successResponse(new DocumentResource($document), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function transferNotePdf(TransferNotePdfRequest $request)
    {
        try {
            $siigoToken = $this->client->token();

            $document = $this->transferNote->find_document($siigoToken, trim($request->input('query')), SiigoTransferNTPdfService::TRANSFER_NOTE);

            if (!$document) {
                throw new \DomainException('No se encontró la nota de traslado en Siigo.');
            }

            $pdf = $this->transferNote->transfer_note_pdf($siigoToken, $document);

            if (!$pdf) {
                throw new \DomainException('No se pudo obtener la vista del documento en Siigo.');
            }

            return $this->downloadPdf($pdf, $this->transferNote->pdf_filename($document));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function purchaseOrderPdf(PurchaseOrderPdfRequest $request)
    {
        try {
            $siigoToken = $this->client->token();

            $document = $this->transferNote->find_document($siigoToken, trim($request->input('query')), SiigoPurchaseOCPdfService::PURCHASE_ORDER);

            if (!$document) {
                throw new \DomainException('No se encontró la orden de compra en Siigo.');
            }

            $pdf = $this->purchaseOrder->purchase_order_pdf($request->input('cookie'), $document);

            if (!$pdf) {
                throw new \DomainException('No se pudo obtener la vista del documento en Siigo. Revisa que la cookie no haya expirado.');
            }

            return $this->downloadPdf($pdf, $this->purchaseOrder->pdf_filename($document));
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

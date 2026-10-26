<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoUserService;
use App\Services\SiigoPurchaseOCPdfService;
use App\Services\SiigoTransferNTPdfService;
use Illuminate\Http\Request;

class SearchDocumentSiigoController extends Controller
{
    /**
     * token: regex que debe cumplir el token de cada tipo; el grupo 1 es el JWT
     * con el que se valida el usuario en Siigo.
     */
    private const DOCUMENT_TYPES = [
        19 => [
            'name'       => 'ORDEN DE COMPRA',
            'route'      => 'siigo.search_document_OC_pdf',
            'token'      => '/(?=.*\.ASPXAUTH=[^;\s]+).*?TKNSGRDDREVENTCALZADOSAS=(eyJ[\w-]+\.[\w-]+\.[\w-]+)/s',
            'token_hint' => 'Cookie completa de Siigo (TKNSGRDDREVENTCALZADOSAS=...; .ASPXAUTH=...).',
        ],
        23 => [
            'name'       => 'NOTA DE TRASLADO',
            'route'      => 'siigo.search_document_NT_pdf',
            'token'      => '/^(eyJ[\w-]+\.[\w-]+\.[\w-]+)$/',
            'token_hint' => 'Solo el token JWT (empieza por eyJ...).',
        ],
    ];

    private const TRANSFER_NOTE = SiigoTransferNTPdfService::TRANSFER_NOTE;
    private const PURCHASE_ORDER = SiigoPurchaseOCPdfService::PURCHASE_ORDER;

    public function __construct(
        private SiigoTransferNTPdfService $transferNote,
        private SiigoPurchaseOCPdfService $purchaseOrder,
        private SiigoClient $client,
        private SiigoUserService $users
    ) {
    }

    public function search_document()
    {
        return view('integration.search_document', [
            'document_types' => self::DOCUMENT_TYPES,
        ]);
    }

    public function search_document_search(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'type'  => 'required',
            'token' => 'required|string',
        ]);

        $queryType = $this->resolve_query_type($request->input('type'));

        if ($queryType === null) {
            return $this->unsupported_type();
        }

        if ($response = $this->unauthorized($request, $queryType)) {
            return $response;
        }

        $siigoToken = $this->client->token();

        $document = $this->transferNote->find_document($siigoToken, trim($request->input('query')), $queryType);

        if (!$document) {
            return response()->json(['document' => null]);
        }

        if ($queryType === self::TRANSFER_NOTE) {
            $document['Items'] = $this->transferNote->parse_transfer_items(
                $this->transferNote->transfer_note_html($siigoToken, $document['ACEntryID'])
            );
        }

        if ($queryType === self::PURCHASE_ORDER) {
            $document['Items'] = $this->purchaseOrder->parse_purchase_items(
                $this->purchaseOrder->purchase_order_html($request->input('token'), $document)
            );
        }

        return response()->json(['document' => $document]);
    }

    public function search_document_NT_pdf(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'type'  => 'required',
            'token' => 'required|string',
        ]);

        if ($this->resolve_query_type($request->input('type')) !== self::TRANSFER_NOTE) {
            return response()->json([
                'success' => false,
                'error'   => 'El PDF solo está disponible para notas de traslado (23).',
            ], 422);
        }

        if ($response = $this->unauthorized($request, self::TRANSFER_NOTE)) {
            return $response;
        }

        $siigoToken = $this->client->token();

        $document = $this->transferNote->find_document($siigoToken, trim($request->input('query')), self::TRANSFER_NOTE);

        if (!$document) {
            return response()->json(['document' => null], 404);
        }

        $pdf = $this->transferNote->transfer_note_pdf($siigoToken, $document);

        if (!$pdf) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo obtener la vista del documento en Siigo.',
            ], 502);
        }

        return $pdf->download($this->transferNote->pdf_filename($document));
    }

    public function search_document_OC_pdf(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'type'  => 'required',
            'token' => 'required|string',
        ]);

        if ($this->resolve_query_type($request->input('type')) !== self::PURCHASE_ORDER) {
            return response()->json([
                'success' => false,
                'error'   => 'El PDF solo está disponible para órdenes de compra (19).',
            ], 422);
        }

        if ($response = $this->unauthorized($request, self::PURCHASE_ORDER)) {
            return $response;
        }

        $siigoToken = $this->client->token();

        $document = $this->transferNote->find_document($siigoToken, trim($request->input('query')), self::PURCHASE_ORDER);

        if (!$document) {
            return response()->json(['document' => null], 404);
        }

        // El monolito se consulta con la cookie completa que mandó el usuario
        $pdf = $this->purchaseOrder->purchase_order_pdf($request->input('token'), $document);

        if (!$pdf) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo obtener la vista del documento en Siigo. Revisa que la cookie no haya expirado.',
            ], 502);
        }

        return $pdf->download($this->purchaseOrder->pdf_filename($document));
    }

    private function resolve_query_type($type): ?int
    {
        return is_numeric($type) && array_key_exists((int) $type, self::DOCUMENT_TYPES)
            ? (int) $type
            : null;
    }

    private function unsupported_type()
    {
        return response()->json([
            'success' => false,
            'error'   => 'Tipo de documento no soportado.',
            'types'   => collect(self::DOCUMENT_TYPES)->map(fn ($type) => $type['name']),
        ], 422);
    }

    /**
     * Saca el JWT del token según el regex del tipo; null si no cumple el formato.
     */
    private function extraer_token(string $token, int $type): ?string
    {
        preg_match(self::DOCUMENT_TYPES[$type]['token'], trim($token), $matches);

        return $matches[1] ?? null;
    }

    private function unauthorized(Request $request, int $type)
    {
        $jwt = $this->extraer_token($request->input('token'), $type);

        if (!$jwt) {
            return response()->json([
                'success' => false,
                'error'   => 'Formato de token inválido para ' . self::DOCUMENT_TYPES[$type]['name'] . '. ' . self::DOCUMENT_TYPES[$type]['token_hint'],
            ], 422);
        }

        $usuario = $this->users->fromToken($jwt);

        if ($usuario['success']) {
            return null;
        }

        return response()->json([
            'success' => false,
            'error'   => $usuario['message'],
        ], 401);
    }
}

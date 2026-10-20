<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Orden de compra (OC) de Siigo: misma salida que la nota de traslado
 * (vista de Siigo + fotos por referencia/color), pero la vista se toma
 * de la página del monolito (Default.aspx?TabID=1671&ERPDocumentID=...),
 * que autentica con la cookie completa de Siigo (TKNSGRDD...; .ASPXAUTH=...).
 */
class SiigoPurchaseOCPdfService
{
    public const PURCHASE_ORDER = 19;

    private const MONOLITH_URL = 'https://monolithprod.siigo.com/REVENTCALZADOSAS/';

    private const PDF_DISK = 'local';
    private const PDF_PATH = 'siigo/ordenes_compra';

    public function __construct(private SiigoTransferNTPdfService $transferNote)
    {
    }

    public function purchase_order_pdf(string $cookie, array $document): ?DomPdf
    {
        $html = $this->purchase_order_html($cookie, $document);

        if (!$html) {
            return null;
        }

        $items = $this->parse_purchase_items($html);
        [$siigoStyle, $siigoBody] = $this->transferNote->split_siigo_html($html);

        return Pdf::loadView('integration.search_document_pdf', [
                'document'   => $document,
                'siigoStyle' => $siigoStyle,
                'siigoBody'  => $siigoBody,
                'groups'     => $this->transferNote->image_groups($items),
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'dpi'             => 135,
            ]);
    }

    /**
     * Genera el PDF de una orden de compra recién creada y lo guarda en disco
     * (lo usa ImportMasivePurchaseOrderSiigoJob para adjuntarlo en los correos).
     * Devuelve ['disk', 'path', 'file'] o null si Siigo no devolvió el documento.
     */
    public function store_purchase_order_pdf(string $cookie, string|int $erpDocumentId, ?string $documentCode = null): ?array
    {
        $documentCode = $documentCode && $documentCode !== '-' ? $documentCode : 'OC-' . $erpDocumentId;

        $document = [
            'ACEntryID'   => $erpDocumentId,
            'Code'        => $documentCode,
            'Description' => null,
            'Url'         => 'Default.aspx?TabID=1671&ERPDocumentID=' . $erpDocumentId,
            'QueryType'   => self::PURCHASE_ORDER,
            'Raw'         => [],
        ];

        $pdf = $this->purchase_order_pdf($cookie, $document);

        if (!$pdf) {
            return null;
        }

        $file = $this->pdf_filename($document);
        $path = self::PDF_PATH . '/' . now()->format('Y_m_d_His') . '_' . $file;

        Storage::disk(self::PDF_DISK)->put($path, $pdf->output());

        return [
            'disk' => self::PDF_DISK,
            'path' => $path,
            'file' => $file,
        ];
    }

    public function pdf_filename(array $document): string
    {
        return 'orden_compra_' . $document['Code'] . '.pdf';
    }

    public function purchase_order_html(string $cookie, array $document): ?string
    {
        $path = $document['Url'] ?: 'Default.aspx?TabID=1671&ERPDocumentID=' . $document['ACEntryID'];
        $url = rtrim(config('services.siigo.monolith_url', self::MONOLITH_URL), '/') . '/' . ltrim($path, '/');

        // Se acepta la cookie tal cual se copia del navegador, con o sin el prefijo "Cookie:"
        $cookie = trim(preg_replace('/^cookie:\s*/i', '', trim($cookie)));

        $request = Http::withHeaders(['Cookie' => $cookie])
            ->accept('text/html')
            ->connectTimeout(30)
            ->timeout(600);

        // Igual que el job: además de la cookie se manda el token que trae adentro
        if (preg_match('/TKNSGRDD\w+=([^;\s]+)/', $cookie, $m)) {
            $request = $request->withToken($m[1]);
        }

        $response = $request->get($url);

        if (!$response->successful()) {
            return null;
        }

        return $this->extract_voucher($response->body());
    }

    /**
     * La página del monolito trae todo el ERP; el documento viene embebido
     * como un <html> completo dentro del div ..._divViewVoucher.
     */
    public function extract_voucher(string $page): ?string
    {
        if (!preg_match('/<div[^>]*id="[^"]*divViewVoucher"[^>]*>(.*?<\/html>)/is', $page, $m)) {
            return null;
        }

        return $m[1];
    }

    public function parse_purchase_items(?string $html): array
    {
        if (!$html) {
            return [];
        }

        // Columnas: Ítem, Descripción, Cantidad, Vr. Unitario, Valor desc., Impto. Cargo, Impto. Rete., Vr. Total
        $rows = (new Crawler($html))
            ->filterXPath('//table[contains(@class,"TableDocumentDetail")]/tbody/tr')
            ->each(function (Crawler $row) {
                $cells = $row->children();

                if ($cells->count() < 8) {
                    return null;
                }

                $text = fn (int $i) => trim(str_replace("\xC2\xA0", ' ', $cells->eq($i)->text('')));

                $description = $text(1);
                $parts = array_map('trim', preg_split('/[-*]/', $description, -1, PREG_SPLIT_NO_EMPTY));
                $count = count($parts);
                $valid = $count === 5 || $count === 4;

                return [
                    'Description'     => $description,
                    'LongDescription' => $description,

                    'Reference' => $valid ? $parts[0] : null,
                    'Color'     => $valid ? $parts[1] : null,
                    'Category'  => $valid ? $parts[$count - 2] : null,
                    'Size'      => $valid ? $parts[$count - 1] : null,

                    'Quantity'  => (float) str_replace(',', '', $text(2)),
                    'UnitValue' => (float) str_replace(',', '', $text(3)),
                    'Total'     => (float) str_replace(',', '', $text(7)),
                ];
            });

        return array_values(array_filter($rows));
    }
}

<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoDocumentService;
use App\Services\Siigo\SiigoLinks;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Lógica compartida de la nota de traslado (NT) de Siigo:
 * la usa SearchDocumentSiigoController para descargar el PDF y
 * ImportMasiveTransferSiigoJob para adjuntarlo en los correos.
 */
class SiigoTransferNTPdfService
{
    public const TRANSFER_NOTE = 23;

    private const DISK = 'public';
    private const BASE_PATH = 'products';
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    private const IMAGE_MAX_WIDTH = 600;

    private const PDF_DISK = 'local';
    private const PDF_PATH = 'siigo/notas_traslado';

    private const HTML_VIEW_PATH = '/isiigo2ACDocumentAPI/api/v1/Documents/ACEntryHtmlViewAllResponse';

    public function __construct(
        private SiigoClient $client,
        private SiigoDocumentService $documents
    ) {
    }

    /**
     * Genera el PDF de la nota de traslado y lo guarda en disco.
     * Devuelve ['disk', 'path', 'file'] o null si Siigo no devolvió el documento.
     */
    public function store_transfer_note_pdf(string $documentCode, string|int|null $acEntryId = null): ?array
    {
        $siigoToken = $this->client->token();

        $document = $this->find_document($siigoToken, trim($documentCode), self::TRANSFER_NOTE);

        // Recién creado puede que la búsqueda rápida de Siigo aún no lo indexe: se usa el id que devolvió el Save
        if (!$document && $acEntryId) {
            $document = $this->document_from_id($acEntryId, $documentCode);
        }

        if (!$document) {
            return null;
        }

        $pdf = $this->transfer_note_pdf($siigoToken, $document);

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

    public function transfer_note_pdf(string $siigoToken, array $document): ?DomPdf
    {
        $html = $this->transfer_note_html($siigoToken, $document['ACEntryID']);

        if (!$html) {
            return null;
        }

        $items = $this->parse_transfer_items($html);
        [$siigoStyle, $siigoBody] = $this->split_siigo_html($html);

        return Pdf::loadView('integration.search_document_pdf', [
                'document'   => $document,
                'siigoStyle' => $siigoStyle,
                'siigoBody'  => $siigoBody,
                'groups'     => $this->image_groups($items),
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'dpi'             => 135,
            ]);
    }

    public function pdf_filename(array $document): string
    {
        return 'nota_traslado_' . $document['Code'] . '.pdf';
    }

    public function find_document(string $siigoToken, string $query, int $queryType): ?array
    {
        $data = $this->documents->quicksearch($siigoToken, $query, $queryType);

        if ($data === null) {
            return null;
        }

        $document = collect($data)->first(function ($item) use ($query) {
            return strcasecmp(trim((string) data_get($item, 'codeEntity')), $query) === 0;
        });

        if (!$document) {
            return null;
        }

        $url = (string) data_get($document, 'urlEntity');

        $acEntryId = data_get($document, 'documentID');

        if (!$acEntryId) {
            if (preg_match('/ERPDocumentID=(\d+)/', $url, $m)) {
                $acEntryId = $m[1];
            } elseif (preg_match('/\/(\d+)\/?$/', $url, $m)) {
                $acEntryId = $m[1];
            }
        }

        return [
            'ACEntryID'   => $acEntryId,
            'Code'        => data_get($document, 'codeEntity'),
            'Description' => data_get($document, 'description'),
            'Url'         => $url,
            'QueryType'   => $queryType,
            'Raw'         => $document,
        ];
    }

    public function transfer_note_html(string $siigoToken, $acEntryId): ?string
    {
        $response = $this->client->services($siigoToken)
            ->get(self::HTML_VIEW_PATH, [
                'ACEntryID' => $acEntryId,
                'OverWrite' => 'true',
            ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json('response') ?? $response->json('result.response');
    }

    public function parse_transfer_items(?string $html): array
    {
        if (!$html) {
            return [];
        }

        $rows = (new Crawler($html))
            ->filterXPath('//table[contains(@class,"TableDocumentDetail")]/tbody/tr')
            ->each(function (Crawler $row) {
                $cells = $row->children();

                if ($cells->count() < 7) {
                    return null;
                }

                $text = fn (int $i) => trim(str_replace("\xC2\xA0", ' ', $cells->eq($i)->text('')));

                $longDescription = $text(2);
                $parts = array_map('trim', preg_split('/[-*]/', $longDescription, -1, PREG_SPLIT_NO_EMPTY));
                $count = count($parts);
                $valid = $count === 5 || $count === 4;

                return [
                    'ProductCode'          => $text(1),
                    'Description'          => $longDescription,
                    'LongDescription'      => $longDescription,
                    'WarehouseOrigin'      => $text(4),
                    'WarehouseDestination' => $text(5),

                    'Reference' => $valid ? $parts[0] : null,
                    'Color'     => $valid ? $parts[1] : null,
                    'Category'  => $valid ? $parts[$count - 2] : null,
                    'Size'      => $valid ? $parts[$count - 1] : null,

                    'Quantity'  => (float) str_replace(',', '', $text(6)),
                ];
            });

        return array_values(array_filter($rows));
    }

    private function document_from_id(string|int $acEntryId, string $documentCode): array
    {
        $url = SiigoLinks::transferNote($acEntryId);

        return [
            'ACEntryID'   => $acEntryId,
            'Code'        => $documentCode,
            'Description' => null,
            'Url'         => $url,
            'QueryType'   => self::TRANSFER_NOTE,
            'Raw'         => [
                'codeEntity' => $documentCode,
                'documentID' => $acEntryId,
                'urlEntity'  => $url,
            ],
        ];
    }

    public function split_siigo_html(string $html): array
    {
        preg_match('/<style[^>]*>(.*?)<\/style>/is', $html, $style);
        preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $bodyMatch);

        $css = $style[1] ?? '';
        $body = $bodyMatch[1] ?? $html;

        // CSS: sin @charset, sin el envoltorio display:table / height:100% (dompdf no lo parte entre páginas) y sin flotantes
        $css = str_replace('@charset "UTF-8";', '', $css);
        $css = preg_replace('/\.invoice-(?:box|container-left|container-right)\s*\{[^}]*\}/', '', $css) ?? $css;
        $css = str_replace(['height:100%;', 'height:inherit;'], '', $css);
        $css = str_replace(['float: left;', 'float: right;'], 'float: none;', $css);

        // Body: sin la tira lateral "Elaborado con Siigo" y sin la tabla anidada de la cantidad
        $body = preg_replace('/<div class="invoice-container-right">.*?<\/table><\/div>/is', '', $body) ?? $body;
        $body = preg_replace('/<table width=[\'"]100%[\'"]>\s*<td><\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/table>/is', '$1', $body) ?? $body;

        return [$css, $body];
    }

    public function image_groups(array $items): Collection
    {
        $disk = Storage::disk(self::DISK);

        $referenceDirs = collect($disk->directories(self::BASE_PATH))
            ->mapWithKeys(fn ($dir) => [$this->normalize(basename($dir)) => $dir]);

        return collect($items)
            ->filter(fn ($item) => !empty($item['Reference']))
            ->groupBy(fn ($item) => $this->normalize($item['Reference']))
            ->flatMap(function ($referenceItems) use ($disk, $referenceDirs) {
                $reference = $referenceItems->first()['Reference'];
                $refDir = $referenceDirs->get($this->normalize($reference));

                $colors = $referenceItems
                    ->groupBy(fn ($item) => $this->normalize($item['Color']))
                    ->map(fn ($colorItems) => [
                        'Color' => $colorItems->first()['Color'],
                        'Sizes' => $colorItems->map(fn ($i) => ['Size' => $i['Size'], 'Quantity' => $i['Quantity']])->values()->all(),
                        'Total' => $colorItems->sum('Quantity'),
                    ])
                    ->values();

                $cards = [];
                $shared = [];

                foreach ($colors as $color) {
                    $image = $refDir ? $this->color_folder_image($disk, $refDir, $color['Color']) : null;

                    if ($image) {
                        // El color tiene su propia carpeta con foto
                        $cards[] = [
                            'Reference' => $reference,
                            'Colors'    => [$color],
                            'Total'     => $color['Total'],
                            'Image'     => $image,
                        ];
                    } else {
                        $shared[] = $color;
                    }
                }

                // Colores sin carpeta propia: una sola tarjeta con la foto raíz de la referencia
                if ($shared) {
                    $cards[] = [
                        'Reference' => $reference,
                        'Colors'    => $shared,
                        'Total'     => collect($shared)->sum('Total'),
                        'Image'     => $refDir ? $this->first_image($disk, $refDir) : null,
                    ];
                }

                return $cards;
            })
            ->values();
    }

    private function color_folder_image($disk, string $refDir, ?string $color): ?string
    {
        $colorDir = collect($disk->directories($refDir))
            ->first(fn ($dir) => $this->normalize(basename($dir)) === $this->normalize($color));

        return $colorDir ? $this->first_image($disk, $colorDir) : null;
    }

    private function first_image($disk, string $dir): ?string
    {
        $files = collect($disk->files($dir))
            ->filter(fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::IMAGE_EXT, true))
            ->sort(SORT_NATURAL)
            ->values();

        foreach ($files as $file) {
            if ($source = $this->image_source($disk, $file)) {
                return $source;
            }
        }

        return null;
    }

    private function image_source($disk, string $file): ?string
    {
        $path = $disk->path($file);

        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'webp') {
            return $path;
        }

        if (!function_exists('imagecreatefromwebp')) {
            return null;
        }

        $image = @imagecreatefromwebp($path);

        if (!$image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $newWidth = min($width, self::IMAGE_MAX_WIDTH);
        $newHeight = (int) round($height * $newWidth / $width);

        // Fondo blanco por si el webp trae transparencia
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 85);
        $jpeg = ob_get_clean();

        return 'data:image/jpeg;base64,' . base64_encode($jpeg);
    }

    private function normalize(?string $value): string
    {
        return Str::upper(Str::ascii(trim((string) $value)));
    }
}

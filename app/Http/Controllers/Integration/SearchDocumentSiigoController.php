<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\SiigoInventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class SearchDocumentSiigoController extends Controller
{
    private const DOCUMENT_TYPES = [
        /*19 => [
            'name'  => 'ORDEN DE COMPRA',
            'route' => 'siigo.search_document_search',
        ],*/
        23 => [
            'name'  => 'NOTA DE TRASLADO',
            'route' => 'siigo.search_document_NT_pdf',
        ],
    ];

    private const TRANSFER_NOTE = 23;

    private const DISK = 'public';
    private const BASE_PATH = 'products';
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    private const IMAGE_MAX_WIDTH = 600;

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

        if ($response = $this->unauthorized($request)) {
            return $response;
        }

        $queryType = $this->resolve_query_type($request->input('type'));

        if ($queryType === null) {
            return $this->unsupported_type();
        }

        $siigoToken = (new SiigoInventoryService())->auth();

        $document = $this->find_document($siigoToken, trim($request->input('query')), $queryType);

        if (!$document) {
            return response()->json(['document' => null]);
        }

        if ($queryType === self::TRANSFER_NOTE) {
            $document['Items'] = $this->parse_transfer_items(
                $this->transfer_note_html($siigoToken, $document['ACEntryID'])
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

        if ($response = $this->unauthorized($request)) {
            return $response;
        }

        if ($this->resolve_query_type($request->input('type')) !== self::TRANSFER_NOTE) {
            return response()->json([
                'success' => false,
                'error'   => 'El PDF solo está disponible para notas de traslado (23).',
            ], 422);
        }

        $siigoToken = (new SiigoInventoryService())->auth();

        $document = $this->find_document($siigoToken, trim($request->input('query')), self::TRANSFER_NOTE);

        if (!$document) {
            return response()->json(['document' => null], 404);
        }

        $html = $this->transfer_note_html($siigoToken, $document['ACEntryID']);

        if (!$html) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo obtener la vista del documento en Siigo.',
            ], 502);
        }

        $items = $this->parse_transfer_items($html);
        [$siigoStyle, $siigoBody] = $this->split_siigo_html($html);
        
        return Pdf::loadView('integration.search_document_NT_pdf', [
                'document'   => $document,
                'siigoStyle' => $siigoStyle,
                'siigoBody'  => $siigoBody,
                'groups'     => $this->image_groups($items),
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'dpi'             => 135,
            ])
            ->download('nota_traslado_' . $document['Code'] . '.pdf');
    }

    private function find_document(string $siigoToken, string $query, int $queryType): ?array
    {
        $response = Http::withToken($siigoToken)
            ->asJson()
            ->post('https://services.siigo.com/cross/api/v2/quicksearch', [
                'quicksearch' => [
                    'queryString'     => $query,
                    'queryType'       => $queryType,
                    'typeQuickSearch' => 0,
                ],
            ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json('result.data', $response->json('data', []));

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

    private function transfer_note_html(string $siigoToken, $acEntryId): ?string
    {
        $response = Http::withToken($siigoToken)
            ->get('https://services.siigo.com/isiigo2ACDocumentAPI/api/v1/Documents/ACEntryHtmlViewAllResponse', [
                'ACEntryID' => $acEntryId,
                'OverWrite' => 'true',
            ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json('response') ?? $response->json('result.response');
    }

    private function parse_transfer_items(?string $html): array
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

    private function split_siigo_html(string $html): array
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

    private function image_groups(array $items): Collection
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

    private function unauthorized(Request $request)
    {
        $usuario = $this->validar_usuario($request->input('token'));

        if ($usuario['success']) {
            return null;
        }

        return response()->json([
            'success' => false,
            'error'   => $usuario['message'],
        ], 401);
    }

    private function validar_usuario(string $token): array
    {
        $usuario = $this->obtener_datos_usuario($token);

        if (!$usuario['success']) {
            return [
                'success' => false,
                'message' => $usuario['message'],
            ];
        }

        return [
            'success' => true,
            'data'    => $usuario['data'],
        ];
    }

    private function obtener_datos_usuario(string $token): array
    {
        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->get('https://services.siigo.com/cross/globalstate/api/v1/Settings/LoadSettings');

        if (!$response->successful()) {
            return [
                'success' => false,
                'data'    => [],
                'message' => 'Error de autenticacion',
            ];
        }

        $data = $response->json();

        return [
            'success' => true,
            'data'    => [
                'id'   => $data['userID'],
                'user' => $data['userName'],
                'name' => $data['UserOptions']['userPrincipalName'],
            ],
            'message' => 'Usuario encontrado exitosamente',
        ];
    }
}
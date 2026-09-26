<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Mail\InvoicePurchaseOrderAccessLink;
use App\Services\SiigoInventoryService;
use Carbon\Carbon;
use App\Mail\InvoicePurchaseOrderConfirmedSiigo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoicePurchaseOrderSiigoController extends Controller
{
    private const DISK = 'public';
    private const BASE_PATH = 'evidences';
    private const INVOICE_PURCHASE_ORDER_ALLOWED_EMAILS = [
        'tecnologia@revent.com.co',
    ];

    public function invoice_purchase_order_reception()
    {
        return view('integration.invoice_purchase_order_reception');
    }

    public function invoice_purchase_order_search(Request $request)
    {
        $token = $request->input('token');
        $usuario = $this->validar_usuario($token);

        if (!$usuario['success']) {
            return response()->json([
                'success' => false,
                'error' => $usuario['message'],
            ], 401);
        }

        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $response = Http::withToken($token)
            ->asJson()
            ->post('https://services.siigo.com/cross/api/v2/quicksearch', [
                'quicksearch' => [
                    'queryString' => $request->input('query'),
                    'queryType' => 19,
                    'typeQuickSearch' => 0,
                ],
            ]);

        if (!$response->successful()) {
            return response()->json([
                'invoice_purchase_order' => null,
            ]);
        }

        $data = $response->json('result.data', []);

        $purchase_order = collect($data)->first(function ($item) use ($request) {
            return data_get($item, 'codeEntity') === $request->input('query');
        });

        if (!$purchase_order) {
            return response()->json([
                'invoice_purchase_order' => null,
            ]);
        }

        $url = data_get($purchase_order, 'urlEntity');

        preg_match('/ERPDocumentID=(\d+)/', $url, $matches);

        $acEntryId = $matches[1] ?? null;

        if (!$acEntryId) {
            return response()->json([
                'invoice_purchase_order' => null,
            ]);
        }

        $cacheKey = "OC-ID-{$acEntryId}";

        $warehouses = $this->warehouses($token);
        $invoice_purchase_order = $this->fetch_detail($token, $acEntryId, $cacheKey);
        $invoice_purchase_order['Items'] = $this->parse_items($invoice_purchase_order, $warehouses);

        return response()->json([
            'invoice_purchase_order' => $invoice_purchase_order,
        ]);
    }

    public function invoice_purchase_order_confirmed(Request $request)
    {
        try {
            $request->validate([
                'receivingData' => ['required', 'string'],
                'imagenes' => ['required', 'array', 'min:1'],
                'imagenes.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:8192'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);
        }

        $data = json_decode($request->input('receivingData'), true);

        if (!is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos de recepción no tienen un formato válido.',
                'errors' => [
                    'receivingData' => ['Los datos de recepción no tienen un formato válido.']
                ]
            ], 422);
        }

        $validator = Validator::make($data, [
            'Order' => ['required', 'array'],
            'Order.ACEntryID' => ['required'],
            'Order.DocName' => ['required', 'string'],
            'Order.DocDate' => ['required', 'string'],
            'Order.Observations' => ['nullable', 'string'],
            'Order.Warehouse' => ['nullable', 'string'],

            'Items' => ['nullable', 'array'],

            'Totals' => ['nullable', 'array'],
            'Totals.Requested' => ['nullable', 'numeric'],
            'Totals.Receiving' => ['nullable', 'numeric'],
            'Totals.Pending' => ['nullable', 'numeric'],

            'Checklist' => ['required', 'array'],
            'Checklist.cajas_master' => ['required', 'boolean'],
            'Checklist.sellos' => ['required', 'boolean'],
            'Checklist.estado_cajas' => ['required', 'boolean'],
            'Checklist.documentos' => ['required', 'boolean'],
            'Checklist.empaque' => ['required', 'boolean'],
            'Checklist.stickers' => ['required', 'boolean'],
            'Checklist.producto' => ['required', 'boolean'],
            'Checklist.exactitud' => ['required', 'boolean'],
            'Checklist.variacion_diseno' => ['required', 'boolean'],
            'Checklist.firma' => ['required', 'boolean'],
            'Checklist.reporte_whatsapp' => ['required', 'boolean'],
            'Checklist.respaldo_whatsapp' => ['required', 'boolean'],
            'Checklist.alerta' => ['required', 'boolean'],
            'Checklist.venta' => ['required', 'boolean'],
            'Checklist.referencias_recibidas' => ['nullable', 'string'],
            'Checklist.observaciones' => ['required', 'string'],

            'Checklist.responsable' => ['required', 'array'],
            'Checklist.responsable.nombre' => ['required', 'string', 'max:150'],
            'Checklist.responsable.cargo' => ['required', 'string', 'max:150'],
            'Checklist.responsable.departamento' => ['required', 'string', 'max:150'],
            'Checklist.responsable.celular' => ['nullable', 'string', 'max:30'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => collect($validator->errors())->flatten()->first() ?? 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $imagenes = [];

        foreach ($request->file('imagenes', []) as $imagen) {
            $parentPathGuid = (string) Str::uuid();
            $filename = $parentPathGuid . '.' . $imagen->getClientOriginalExtension();
            $path = self::BASE_PATH;

            Storage::disk(self::DISK)->putFileAs($path, $imagen, $filename);

            $imagenes[] = Storage::disk(self::DISK)->url("{$path}/{$filename}");
        }

        Mail::to(['camiloacacio16@gmail.com'])->send(
            new InvoicePurchaseOrderConfirmedSiigo($data, $imagenes)
        );

        return response()->json([
            'success' => true,
            'message' => 'La recepción fue registrada y el correo fue enviado correctamente.',
        ]);
    }

    public function invoice_purchase_order_access()
    {
        return view('integration.invoice_purchase_order_access');
    }

    public function invoice_purchase_order_send_access_link(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->input('email')));

        if (!in_array($email, self::INVOICE_PURCHASE_ORDER_ALLOWED_EMAILS, true)) {
            return back()
                ->withErrors(['email' => 'Este correo no tiene autorización para acceder a ordenes de compra.'])
                ->withInput();
        }

        try {
            $url = URL::temporarySignedRoute('siigo.invoice_purchase_order', now()->addHours(24));

            Mail::to($email)->send(new InvoicePurchaseOrderAccessLink($url));
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['email' => 'No fue posible enviar el enlace de acceso. Intenta nuevamente en unos minutos.'])
                ->withInput();
        }

        return back()->with('status', 'Te enviamos el enlace de acceso a ' . $email . '. Revisa tu bandeja de entrada (y spam).');
    }

    public function invoice_purchase_order()
    {
        return view('integration.invoice_purchase_order');
    }

    public function invoice_purchase_order_documents(Request $request)
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $users = $this->users($token);
        $warehouses = $this->warehouses($token);

        $fecha_inicio = Carbon::now()->subDays(31);
        $fecha_fin = Carbon::now();

        $invoices = $this->purchase_invoices($token, $fecha_inicio, $fecha_fin);
        $invoice_details = $this->details($token, $invoices->pluck('ACEntryID'), 'FC-ID');

        $purchase_invoices = $invoices
            ->map(function ($invoice) use ($invoice_details, $users, $warehouses) {
                $detail = $invoice_details->get($invoice['ACEntryID']);

                $user = $users->get(data_get($detail, 'Entry.SalesmanCode'));
                $invoice['User'] = trim(data_get($user, 'first_name', '') . ' ' . data_get($user, 'last_name', ''));
                $invoice['ACEntryCode'] = data_get($detail, 'Entry.ACEntryCode');
                $invoice['Items'] = $this->parse_items($detail, $warehouses);
                $invoice['Link'] = "https://siigonube.siigo.com/#/purchase/1008/{$invoice['ACEntryID']}";

                return $invoice;
            })
            ->groupBy('ACEntryCode');

        // Órdenes
        $orders = $this->purchase_orders($token, $fecha_inicio, $fecha_fin);
        $order_details = $this->details($token, $orders->pluck('ACEntryID'), 'OC-ID');
        $providers = collect(Cache::many($orders->pluck('MsThirdPartyID')->filter()->unique()->values()->all()));

        $purchase_orders = $orders->map(function ($order) use ($order_details, $providers, $purchase_invoices, $users, $warehouses) {
            $detail = $order_details->get($order['ACEntryID']);
            $provider = $providers->get($order['MsThirdPartyID']);
            $comments = trim(data_get($provider, 'Comments', ''));
            $order_invoices = $purchase_invoices->get($order['ACEntryID'], collect());

            $order['Observations'] = trim(data_get($detail, 'Entry.Observations', ''));
            $order['FullName'] = strtoupper($order['FullName']);
            $order['CompanyName'] = strtoupper(data_get($provider, 'BasicData.CompanyName', ''));
            $order['TypeProvider'] = str_starts_with($comments, 'PT') ? 'PRODUCTO TERMINADO' : 'OTROS';

            preg_match('/FECHA LIMITE: (\d{4}-\d{2}-\d{2})/', $order['Observations'], $match);
            $order['DeadlineDate'] = $match[1] ?? null;

            // Items de la OC + en qué facturas se confirmaron y cuántos
            $items = collect($this->parse_items($detail, $warehouses))->map(function ($item) use ($order_invoices) {
                $confirmed_in = $order_invoices
                    ->map(function ($invoice) use ($item) {
                        $quantity = collect($invoice['Items'])
                            ->where('ProductCode', $item['ProductCode'])
                            ->sum('Quantity');

                        return $quantity > 0 ? [
                            'ACEntryID' => $invoice['ACEntryID'],
                            'DocName'   => $invoice['DocName'] ?? null,
                            'Quantity'  => $quantity,
                            'Link'      => $invoice['Link'],
                        ] : null;
                    })
                    ->filter()
                    ->values();

                $item['Confirmed'] = $confirmed_in->sum('Quantity');
                $item['Pending'] = $item['Confirmed'] - $item['Quantity'];
                $item['Invoices'] = $confirmed_in->all();

                return $item;
            });


            $user = $users->get(data_get($detail, 'Entry.SalesmanCode'));
            $order['User'] = trim(data_get($user, 'first_name', '') . ' ' . data_get($user, 'last_name', ''));
            $order['Items'] = $items->all();
            $order['TotalQuantity'] = $items->sum('Quantity');
            $order['TotalConfirmed'] = $items->sum('Confirmed');
            $order['Invoices'] = $order_invoices
                ->map(fn ($invoice) => collect($invoice)->only([
                    'ACEntryID', 'DocName', 'ExternalDocumentNumber', 'DocDate', 'TotalValue', 'Link', 'User'
                ]))
                ->values();
            $order['Warehouse'] = collect($detail['Items'] ?? [])->pluck('WarehouseCode')->filter()->unique()
                ->map(fn ($code) => $code . ' - ' . ($warehouses->get($code)['name'] ?? ''))->implode(', ');
            $order['Link'] = "https://siigonube.siigo.com/#/asp/" . base64_encode("Default.aspx?TabID=1671&ERPDocumentID={$order['ACEntryID']}") . "?TabID=1671";

            return $order;
        });

        return response()->json([
            'purchase_orders' => $purchase_orders,
        ]);
    }

    private function parse_items(?array $detail, ?Collection $warehouses = null): array
    {
        return collect($detail['Items'] ?? [])
            ->filter(fn ($item) => !empty($item['ProductCode']))
            ->map(function ($item) use ($warehouses) {
                $parts = preg_split('/[-*]/', $item['LongDescription'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
                $parts = array_map('trim', $parts);
                $count = count($parts);
                $valid = $count === 5 || $count === 4;

                $warehouseCode = $item['WarehouseCode'] ?? null;
                $warehouseName = $warehouses?->get($warehouseCode)['name'] ?? null;

                return [
                    'ProductCode'        => $item['ProductCode'],
                    'Description'        => $item['Description'] ?? null,
                    'LongDescription'    => $item['LongDescription'] ?? null,
                    'WarehouseCode'      => $warehouseCode,
                    'Warehouse'          => $warehouseCode !== null
                        ? $warehouseCode . ' - ' . ($warehouseName ?? '')
                        : null,

                    'Reference'          => $valid ? $parts[0] : null,
                    'Color'              => $valid ? $parts[1] : null,
                    'Category'           => $valid ? $parts[$count - 2] : null,
                    'Size'               => $valid ? $parts[$count - 1] : null,

                    'Quantity'           => $item['Quantity'] ?? 0,
                    'UnitValue'          => ($item['UnitValue'] ?? 0) + ($item['TaxAddValue'] ?? 0) - ($item['TaxDiscValue'] ?? 0),
                    'Value'              => $item['Value'] ?? 0,

                    'DiscountPercentage' => $item['DiscountPercentage'] ?? 0,
                    'DiscountValue'      => $item['DiscountValue'] ?? 0,

                    'TaxAddName'         => $item['TaxAddName'] ?? null,
                    'TaxAddPercentage'   => $item['TaxAddPercentage'] ?? 0,
                    'TaxAddValue'        => $item['TaxAddValue'] ?? 0,
                    'TaxDiscName'        => $item['TaxDiscName'] ?? null,
                    'TaxDiscPercentage'  => $item['TaxDiscPercentage'] ?? 0,
                    'TaxDiscValue'       => $item['TaxDiscValue'] ?? 0,
                ];
            })
            ->values()
            ->all();
    }

    private function purchase_orders(string $token, ?Carbon $fecha_inicio = null, ?Carbon $fecha_fin = null)
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        $fecha_inicio = $fecha_inicio ?? Carbon::now()->subMonth();
        $fecha_fin = $fecha_fin ?? Carbon::now();

        $source = collect(range(2015, $fecha_fin->year))
            ->map(fn ($anio) => [
                'id' => $anio,
                'StartDate' => "{$anio}0101",
                'EndDate' => "{$anio}1231",
            ])
            ->values()
            ->toArray();

        do {
            $filterCriterias = [
                [
                    'Field' => '_vTypeTransaction',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => ['4'],
                    'ValueUI' => 'Orden de compra',
                    'Source' => 'PurchasesTransactionEnum',
                ],
                [
                    'Field' => '_vProvider',
                    'FilterType' => 68,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => 'Account',
                ],
                [
                    'Field' => '_vDocDate',
                    'FilterType' => 76,
                    'OperatorType' => 0,
                    'Value' => [
                        $fecha_inicio->format('Ymd'),
                        $fecha_fin->format('Ymd'),
                    ],
                    'ValueUI' => $fecha_inicio->format('Y/m/d')
                        . ' - '
                        . $fecha_fin->format('Y/m/d'),
                    'Source' => $source,
                ],
                [
                    'Field' => '_vUser',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '12',
                ],
                [
                    'Field' => '_vProviderInvoice',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '64',
                ],
                [
                    'Field' => '_vESiigoStatus',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => ['-1'],
                    'ValueUI' => '',
                    'Source' => 'DianStateFilterEnum',
                ],
            ];

            $body = [
                'Id' => 5451,
                'Skip' => $skip,
                'Take' => $take,
                'Sort' => ' ',
                'FilterCriterias' => json_encode($filterCriterias),
                'Params' => json_encode([
                    'TabID' => '1408',
                ]),
                'GetTotalCount' => $total === null,
                'GridOrderCriteria' => null,
                'AddOns' => [
                    [
                        'name' => 'POS Web',
                        'state' => true,
                        'tenantId' => '0x00000000000000000000000000605286',
                        'type' => 1,
                        'module' => 5,
                        'dateActive' => '09/19/2026 08:55:44.118',
                        'posActiveCashiers' => [
                            'baseCashiers' => 1,
                            'aditionalCashiers' => 28,
                        ],
                        'documentBase' => 0,
                        'readOnly' => null,
                        'subState' => 1,
                        'updateType' => 1,
                        'complements' => null,
                        'payrollComplements' => null,
                    ],
                ],
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(600)
                ->connectTimeout(30)
                ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

            if (!$response->successful()) {
                throw new \Exception(
                    'Error consultando órdenes de compra: ' . $response->body()
                );
            }

            if ($total === null) {
                $total = (int) $response->json('totalCount');
            }

            $page = $response->json('data.Value.Table') ?? [];

            $rows = array_merge($rows, $page);

            $skip += $take;

        } while ($skip < $total);

        return collect($rows);
    }

    private function purchase_invoices(string $token, ?Carbon $fecha_inicio = null, ?Carbon $fecha_fin = null)
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        $fecha_inicio = $fecha_inicio ?? Carbon::now()->subMonth();
        $fecha_fin = $fecha_fin ?? Carbon::now();

        $source = collect(range(2015, $fecha_fin->year))
            ->map(fn ($anio) => [
                'id' => $anio,
                'StartDate' => "{$anio}0101",
                'EndDate' => "{$anio}1231",
            ])
            ->values()
            ->toArray();

        do {
            $filterCriterias = [
                [
                    'Field' => '_vTypeTransaction',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => ['0'],
                    'ValueUI' => 'Compra',
                    'Source' => 'PurchasesTransactionEnum',
                ],
                [
                    'Field' => '_vProvider',
                    'FilterType' => 68,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => 'Account',
                ],
                [
                    'Field' => '_vDocDate',
                    'FilterType' => 76,
                    'OperatorType' => 0,
                    'Value' => [
                        $fecha_inicio->format('Ymd'),
                        $fecha_fin->format('Ymd'),
                    ],
                    'ValueUI' => $fecha_inicio->format('Y/m/d')
                        . ' - '
                        . $fecha_fin->format('Y/m/d'),
                    'Source' => $source,
                ],
                [
                    'Field' => '_vUser',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '12',
                ],
                [
                    'Field' => '_vProviderInvoice',
                    'FilterType' => 6,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => '64',
                ],
                [
                    'Field' => '_vESiigoStatus',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => ['-1'],
                    'ValueUI' => '',
                    'Source' => 'DianStateFilterEnum',
                ],
            ];

            $body = [
                'Id' => 5451,
                'Skip' => $skip,
                'Take' => $take,
                'Sort' => ' ',
                'FilterCriterias' => json_encode($filterCriterias),
                'Params' => json_encode([
                    'TabID' => '1408',
                ]),
                'GetTotalCount' => $total === null,
                'GridOrderCriteria' => null,
                'AddOns' => [
                    [
                        'name' => 'POS Web',
                        'state' => true,
                        'tenantId' => '0x00000000000000000000000000605286',
                        'type' => 1,
                        'module' => 5,
                        'dateActive' => '09/19/2026 08:55:44.118',
                        'posActiveCashiers' => [
                            'baseCashiers' => 1,
                            'aditionalCashiers' => 28,
                        ],
                        'documentBase' => 0,
                        'readOnly' => null,
                        'subState' => 1,
                        'updateType' => 1,
                        'complements' => null,
                        'payrollComplements' => null,
                    ],
                ],
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(600)
                ->connectTimeout(30)
                ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

            if (!$response->successful()) {
                throw new \Exception(
                    'Error consultando facturas de compra: ' . $response->body()
                );
            }

            if ($total === null) {
                $total = (int) $response->json('totalCount');
            }

            $page = $response->json('data.Value.Table') ?? [];

            $rows = array_merge($rows, $page);

            $skip += $take;

        } while ($skip < $total);

        return collect($rows);
    }

    private function details(string $token, $ids, string $prefix)
    {
        $ids = collect($ids)->filter()->unique()->values();

        $cached = Cache::many($ids->map(fn ($id) => "{$prefix}-{$id}")->all());

        return $ids->mapWithKeys(function ($id) use ($token, $prefix, $cached) {
            $key = "{$prefix}-{$id}";

            return [$id => $cached[$key] ?? $this->fetch_detail($token, $id, $key)];
        });
    }

    private function fetch_detail(string $token, int $acEntryId, string $cacheKey)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->get('https://services.siigo.com/ACEntryApi/api/v2/Purchase/GetItem', [
                'id' => $acEntryId,
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                "Error consultando detalle {$cacheKey}: " . $response->body()
            );
        }

        $data = $response->json();

        if (empty($data)) {
            Cache::put($cacheKey, [], now()->addHour());

            return [];
        }

        Cache::forever($cacheKey, $data);

        return $data;
    }

    private function warehouses(string $token)
    {
        $response = Http::retry(5, 10000)->withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id'    => 'consultadeFacturas',
        ])->get("https://api.siigo.com/v1/warehouses");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        return collect($data)->keyBy('id')
            ->put(-1, [
                'id' => -1,
                'name' => 'SIN ASIGNAR',
            ]);
    }

    private function users(string $token)
    {
        $page = 1;
        $page_size = 100;
        $total_pages = null;
        $users = [];

        do {
            $response = Http::retry(5, 10000)->timeout(180)->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $token,
                'Partner-Id' => 'consultadeFacturas',
            ])->get("https://api.siigo.com/v1/users", [
                'page' => $page,
                'page_size' => $page_size,
            ]);

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            $data = $response->json();

            if (!empty($data['results'])) {
                $users = array_merge($users, $data['results']);
            }

            if ($total_pages === null) {
                $pagination = $data['pagination'];
                $total_pages = (int) ceil($pagination['total_results'] / $pagination['page_size']);
            }

            $page++;
        } while ($page <= $total_pages);

        return $users = collect($users)->keyBy('id');
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
            'data' => $usuario['data'],
        ];
    }

    private function obtener_datos_usuario(string $token)
    {
        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->get('https://services.siigo.com/cross/globalstate/api/v1/Settings/LoadSettings');

        if (!$response->successful()) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'Error de autenticacion'
            ];
        }

        $data = $response->json();

        return [
            'success' => true,
            'data' => [
                'id' => $data['userID'],
                'user' => $data['userName'],
                'name' => $data['UserOptions']['userPrincipalName'],
            ],
            'message' => 'Usuario encontrado exitosamente'
        ];
    }
}

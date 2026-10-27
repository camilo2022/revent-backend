<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Mail\InvoicePurchaseOrderAccessLinkMail;
use App\Http\Controllers\Integration\Concerns\SendsAccessLink;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoDocumentService;
use App\Services\Siigo\SiigoLinks;
use App\Services\Siigo\SiigoUserService;
use App\Services\SiigoPurchaseOrderCacheService;
use App\Exports\InvoicePurchaseOrderMissingExport;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Mail\InvoicePurchaseOrderConfirmedSiigoMail;
use App\Services\SiigoProductsCache;
use App\Http\Requests\Integration\InvoicePurchaseOrder\ConfirmedRequest;
use App\Http\Requests\Integration\InvoicePurchaseOrder\SearchRequest;
use App\Http\Requests\Integration\InvoicePurchaseOrder\SendAccessLinkRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class InvoicePurchaseOrderSiigoController extends Controller
{
    private const DISK = 'public';
    private const BASE_PATH = 'evidences';

    use SendsAccessLink;

    public function __construct(
        private SiigoClient $client,
        private SiigoUserService $siigoUsers,
        private SiigoCatalogService $catalog,
        private SiigoDocumentService $siigoDocuments,
        private SiigoPurchaseOrderCacheService $purchaseCache
    ) {
    }

    public function invoice_purchase_order_reception()
    {
        return view('integration.invoice_purchase_order.reception');
    }

    public function invoice_purchase_order_search(SearchRequest $request)
    {
        $token = $request->input('token');
        $usuario = $this->siigoUsers->fromToken($token);

        if (!$usuario['success']) {
            return response()->json([
                'success' => false,
                'error' => $usuario['message'],
            ], 401);
        }

        $token = $this->client->token();

        $data = $this->siigoDocuments->quicksearch($token, (string) $request->input('query'), 19);

        if ($data === null) {
            return response()->json([
                'invoice_purchase_order' => null,
            ]);
        }

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

        $warehouses = $this->catalog->warehousesById($token);
        $invoice_purchase_order = $this->purchaseCache->fetchDetail($token, (int) $acEntryId, $cacheKey);
        $invoice_purchase_order['Items'] = $this->parse_items($invoice_purchase_order, $warehouses);

        return response()->json([
            'invoice_purchase_order' => $invoice_purchase_order,
        ]);
    }

    public function invoice_purchase_order_confirmed(ConfirmedRequest $request)
    {
        // El formato del payload y de las imágenes ya lo validó ConfirmedRequest;
        // aquí se comprueba el token antes de guardar nada.
        $token = $request->input('token');
        $usuario = $this->siigoUsers->fromToken($token);

        if (!$usuario['success']) {
            return response()->json([
                'success' => false,
                'message' => $usuario['message'],
            ], 401);
        }

        $data = json_decode($request->input('receivingData'), true);

        $imagenes = [];

        foreach ($request->file('imagenes', []) as $imagen) {
            $parentPathGuid = (string) Str::uuid();
            $filename = $parentPathGuid . '.' . $imagen->getClientOriginalExtension();
            $path = self::BASE_PATH;

            Storage::disk(self::DISK)->putFileAs($path, $imagen, $filename);

            $imagenes[] = Storage::disk(self::DISK)->url("{$path}/{$filename}");
        }

        Mail::to([config('siigo.mail.purchase_reception')])->send(
            new InvoicePurchaseOrderConfirmedSiigoMail($data, $usuario, $imagenes)
        );

        return response()->json([
            'success' => true,
            'message' => 'La recepción fue registrada y el correo fue enviado correctamente.',
        ]);
    }

    public function invoice_purchase_order_access()
    {
        return view('integration.invoice_purchase_order.access');
    }

    public function invoice_purchase_order_send_access_link(SendAccessLinkRequest $request)
    {
        return $this->sendAccessLink(
            $request,
            config('siigo.access.invoice_purchase_order'),
            'siigo.invoice_purchase_order',
            'ordenes de compra',
            fn (string $url) => new InvoicePurchaseOrderAccessLinkMail($url)
        );
    }

    public function invoice_purchase_order()
    {
        return view('integration.invoice_purchase_order.index');
    }

    public function invoice_purchase_order_documents()
    {
        ini_set('memory_limit', '-1');

        $token = $this->client->token();
        $users = $this->catalog->usersById($token);
        $warehouses = $this->catalog->warehousesById($token);
        $productsByCode = app(SiigoProductsCache::class)->keyedByProductId();

        // Cache: histórico | En vivo: solo hoy
        //$fecha_inicio = Carbon::now()->subMonths(5)->startOfDay();
        $fecha_inicio = Carbon::parse('2026-04-01')->startOfDay();
        $ayer = Carbon::yesterday();
        $hoy_inicio = Carbon::today();
        $hoy_fin = Carbon::now();

        // Facturas históricas: solo caché. Facturas de hoy: consulta en vivo.
        $invoices_cached = $this->cachedDocumentsByDay('FC', $fecha_inicio, $ayer);
        $invoices_today = $this->purchaseCache->purchaseInvoices($token, $hoy_inicio, $hoy_fin);
        $invoices = $invoices_cached->merge($invoices_today)->unique('ACEntryID')->values();

        $invoice_details = $this->details($token, $invoices->pluck('ACEntryID'), 'FC-ID', $invoices_today->pluck('ACEntryID'));

        $purchase_invoices = $invoices->map(function ($invoice) use ($invoice_details, $users, $warehouses, $productsByCode) {

                $detail = $invoice_details->get($invoice['ACEntryID']);
                $user = $users->get(data_get($detail, 'Entry.SalesmanCode'));
                $invoice['User'] = trim(data_get($user, 'first_name', '') . ' ' . data_get($user, 'last_name', ''));
                $invoice['ACEntryCode'] = data_get($detail, 'Entry.ACEntryCode');

                $invoice['Items'] = collect($this->parse_items($detail, $warehouses))->map(function ($item) use ($productsByCode) {
                    $producto = $productsByCode->get($item['ProductCode'] ?? null);
                    $item['Model'] = $producto['model'] ?? '';
                    return $item;
                })->values()->all();

                $invoice['Link'] = SiigoLinks::purchaseInvoice($invoice['ACEntryID']);

                return $invoice;
            })
            ->groupBy('ACEntryCode');

        // Órdenes históricas: solo caché. Órdenes de hoy: consulta en vivo.
        $orders_cached = $this->cachedDocumentsByDay('OC', $fecha_inicio, $ayer);
        $orders_today = $this->purchaseCache->purchaseOrders($token, $hoy_inicio, $hoy_fin);
        $orders = $orders_cached->merge($orders_today)->unique('ACEntryID')->values();

        $order_details = $this->details($token, $orders->pluck('ACEntryID'), 'OC-ID', $orders_today->pluck('ACEntryID'));

        $providers = collect(Cache::many($orders->pluck('MsThirdPartyID')->filter()->unique()->values()->all()));

        $purchase_orders = $orders->map(function ($order) use ($order_details, $providers, $purchase_invoices, $users, $warehouses, $productsByCode) {
            $detail = $order_details->get($order['ACEntryID']);
            $fechaOrden = data_get($order, 'DocDate');

            if ($fechaOrden) {
                $fechaOrden = Carbon::parse($fechaOrden)->startOfDay();
                $hoy = Carbon::today();

                if ($fechaOrden->lt($hoy->copy()->subMonthsNoOverflow(4))) {
                    $order['AgingStatus'] = 'age-red';
                } elseif ($fechaOrden->lt($hoy->copy()->subMonthsNoOverflow(1))) {
                    $order['AgingStatus'] = 'age-orange';
                } else {
                    $order['AgingStatus'] = 'age-green';
                }
            } else {
                $order['AgingStatus'] = 'age-blue';
            }

            $provider = $providers->get($order['MsThirdPartyID']);

            $comments = trim(data_get($provider, 'Comments', ''));

            $order_invoices = $purchase_invoices->get($order['ACEntryID'], collect());

            $order['Observations'] = trim(data_get($detail, 'Entry.Observations', ''));
            $order['FullName'] = strtoupper($order['FullName']);
            $order['CompanyName'] = strtoupper(data_get($provider, 'BasicData.CompanyName', ''));
            $order['TypeProvider'] = str_starts_with($comments, 'PT') ? 'PRODUCTO TERMINADO' : 'OTROS';

            preg_match('/FECHA LIMITE: (\d{4}-\d{2}-\d{2})/', $order['Observations'], $match);
            $order['DeadlineDate'] = $match[1] ?? null;

            $confirmedByProduct = [];

            foreach ($order_invoices as $invoice) {
                foreach (($invoice['Items'] ?? []) as $invoiceItem) {
                    $longDescription = trim($invoiceItem['LongDescription'] ?? '');
                    $quantity = $invoiceItem['Quantity'] ?? 0;

                    if ($longDescription === '' || $quantity <= 0) {
                        continue;
                    }

                    $confirmedByProduct[$longDescription][] = [
                        'ACEntryID' => $invoice['ACEntryID'],
                        'DocName' => $invoice['DocName'] ?? null,
                        'Quantity' => $quantity,
                        'Link' => $invoice['Link'],
                        'Item' => $invoiceItem,
                    ];
                }
            }

            $items = collect($this->parse_items($detail, $warehouses))->map(function ($item) use ($confirmedByProduct, $productsByCode) {
                $longDescription = trim($item['LongDescription'] ?? '');
                $confirmed_in = $confirmedByProduct[$longDescription] ?? [];

                $item['Confirmed'] = array_sum(array_column($confirmed_in, 'Quantity'));
                $item['Pending'] = $item['Confirmed'] - $item['Quantity'];
                $item['Invoices'] = collect($confirmed_in)->map(fn ($invoice) => collect($invoice)->except('Item')->all())->values()->all();
                $producto = $productsByCode->get($item['ProductCode'] ?? null);
                $item['Model'] = $producto['model'] ?? '';

                return $item;
            });

            $existingProducts = $items->pluck('LongDescription')
                ->map(fn ($description) => trim($description ?? ''))
                ->all();

            foreach ($confirmedByProduct as $longDescription => $invoiceItems) {
                if (in_array($longDescription, $existingProducts, true)) {
                    continue;
                }

                $invoiceItem = $invoiceItems[0]['Item'];

                $confirmed = array_sum(array_column($invoiceItems, 'Quantity'));

                $invoiceReferences = collect($invoiceItems)
                    ->map(fn ($invoice) => collect($invoice)->except('Item')->all())
                    ->values()
                    ->all();

                $invoiceItem['Quantity'] = 0;
                $invoiceItem['Confirmed'] = $confirmed;
                $invoiceItem['Pending'] = $confirmed;
                $invoiceItem['Invoices'] = $invoiceReferences;

                $items->push($invoiceItem);
                $existingProducts[] = $longDescription;
            }

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

            $order['Warehouse'] = collect($detail['Items'] ?? [])
                ->pluck('WarehouseCode')
                ->filter()
                ->unique()
                ->map(fn ($code) => $code . ' - ' . ($warehouses->get($code)['name'] ?? ''))
                ->implode(', ');

            $order['Link'] = SiigoLinks::purchaseOrder($order['ACEntryID']);

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

    private function cachedDocumentsByDay(string $type, Carbon $from, Carbon $to): Collection
    {
        $days = collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(fn ($day) => $day->toDateString());

        $keys = $days->map(fn ($day) => SiigoPurchaseOrderCacheService::dayKey($type, $day))->all();
        $cached = Cache::many($keys);
        $rows = collect();

        foreach ($days as $day) {
            $key = SiigoPurchaseOrderCacheService::dayKey($type, $day);

            if (!array_key_exists($key, $cached) || $cached[$key] === null) {
                continue;
            }

            $rows = $rows->merge($cached[$key]);
        }

        return $rows->unique('ACEntryID')->values();
    }

    private function details(string $token, $ids, string $prefix, $fetchMissingIds = []): Collection
    {
        $ids = collect($ids)->filter()->unique()->values();
        $fetchMissingIds = collect($fetchMissingIds)->map(fn ($id) => (string) $id)->flip();
        $cached = Cache::many($ids->map(fn ($id) => "{$prefix}-{$id}")->all());

        return $ids->mapWithKeys(function ($id) use ($token, $prefix, $cached, $fetchMissingIds) {
            $key = "{$prefix}-{$id}";

            if (array_key_exists($key, $cached) && $cached[$key] !== null) {
                return [$id => $cached[$key]];
            }

            if ($fetchMissingIds->has((string) $id)) {
                return [$id => $this->purchaseCache->fetchDetail($token, (int) $id, $key)];
            }

            return [$id => []];
        });
    }

    public function invoice_purchase_order_missing_download()
    {
        ini_set('memory_limit', '-1');

        $response = $this->invoice_purchase_order_documents();

        $data = $response->getData(true);

        $purchaseOrders = collect($data['purchase_orders'] ?? []);

        $purchaseOrders = $purchaseOrders->whereNotIn('DocName', [
            'OC-1-9050', 'OC-1-8994', 'OC-1-9027', 'OC-1-9806', 'OC-1-9808', 'OC-1-9810', 'OC-1-9811', 'OC-1-9802', 'OC-1-9809', 'OC-1-9807', 'OC-1-9813', 'OC-1-9804',
            'OC-1-9803', 'OC-1-9812', 'OC-1-9805', 'OC-1-10752', 'OC-1-11325', 'OC-1-11327', 'OC-1-11318', 'OC-1-11330', 'OC-1-12659', 'OC-1-12397', 'OC-1-10821',
            'OC-1-10816', 'OC-1-10812', 'OC-1-10813', 'OC-1-10819', 'OC-1-10817', 'OC-1-10822', 'OC-1-10823', 'OC-1-12174', 'OC-1-10815', 'OC-1-10832', 'OC-1-10824',
            'OC-1-10825', 'OC-1-10829', 'OC-1-10826', 'OC-1-10830', 'OC-1-10834', 'OC-1-10912', 'OC-1-10911', 'OC-1-10913', 'OC-1-10880', 'OC-1-10907', 'OC-1-10874',
            'OC-1-10873', 'OC-1-10876', 'OC-1-10875', 'OC-1-10910', 'OC-1-10908', 'OC-1-10906', 'OC-1-10905', 'OC-1-10878', 'OC-1-10877', 'OC-1-10904', 'OC-1-10909',
            'OC-1-10914', 'OC-1-10915', 'OC-1-10916', 'OC-1-10879', 'OC-1-9369', 'OC-1-9414', 'OC-1-9476', 'OC-1-9422', 'OC-1-9424', 'OC-1-9443', 'OC-1-9653', 'OC-1-9652',
            'OC-1-9654', 'OC-1-9642', 'OC-1-9650', 'OC-1-9640', 'OC-1-9649', 'OC-1-9660', 'OC-1-9659', 'OC-1-9661', 'OC-1-9658', 'OC-1-9635', 'OC-1-10571', 'OC-1-10574'
        ]);

        $rows = $purchaseOrders->flatMap(function ($order) {

            $requestedTotal = (float) ($order['TotalQuantity'] ?? 0);
            $receivedTotal = (float) ($order['TotalConfirmed'] ?? 0);

            if ($requestedTotal <= $receivedTotal) {
                return collect();
            }

            return collect($order['Items'] ?? [])
                ->groupBy(function ($item) {
                    return mb_strtoupper(trim($item['Reference'] ?? '')) . '|' . mb_strtoupper(trim($item['Color'] ?? ''));
                })
                ->filter(function ($group) {
                    return $group->sum(fn ($item) => (float) ($item['Confirmed'] ?? 0)) <= 0;
                })
                ->flatten(1)
                ->map(function ($item) use ($order) {

                    $requested = (float) ($item['Quantity'] ?? 0);
                    $received = (float) ($item['Confirmed'] ?? 0);

                    $date = $order['DocDate'] ?? null;

                    return [
                        'DocName' => $order['DocName'],
                        'FullName' => $order['FullName'],
                        'CompanyName' => $order['CompanyName'],
                        'Identification' => $order['Identification'],
                        'Date' => $date ? Carbon::parse($date)->format('d/m/Y') : '',
                        'Description' => $item['LongDescription'] ?? $item['Description'] ?? '',
                        'Reference' => $item['Reference'] ?? '',
                        'Model' => $item['Model'] ?? '',
                        'Color' => $item['Color'] ?? '',
                        'Size' => $item['Size'] ?? '',
                        'Warehouse' => $item['Warehouse'] ?? '',
                        'Requested' => $requested,
                        'Received' => $received,
                        'Pending' => max(0, $requested - $received),
                        'ACEntryID' => $order['ACEntryID'] ?? null,
                    ];
                });
        })->values()->all();

        return Excel::download(new InvoicePurchaseOrderMissingExport($rows), 'referencias_faltantes_' . now()->format('Ymd_His') . '.xlsx');
    }
}

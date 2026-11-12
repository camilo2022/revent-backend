<?php

namespace App\Services\Integration;

use App\Models\User;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoLinks;
use App\Services\SiigoProductsCache;
use App\Services\SiigoPurchaseOrderCacheService;
use App\Support\Integration\LegacyCacheKeys;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Seguimiento de órdenes de compra: cada orden con sus ítems, lo confirmado en facturas de compra y lo que falta,
 * y las filas del Excel de referencias faltantes.
 *
 * Copiado de Integration\InvoicePurchaseOrderSiigoController: invoice_purchase_order_documents,
 * invoice_purchase_order_missing_download, cachedDocumentsByDay y details (commit b8694f73), incluida la lista de
 * órdenes excluidas. Si cambia alguno de esos métodos o esa lista en el controlador legacy (la lista se actualiza
 * cada semana), hay que replicarlo aquí.
 *
 * Lee las mismas claves de caché que Integration (listado por día, detalle OC-ID/FC-ID y proveedor): ver
 * LegacyCacheKeys. Diferencia con el legacy: cada orden trae `WarehouseCodes` y se entregan solo las órdenes con
 * alguna bodega a la que el usuario tiene acceso (todas, si tiene acceso a todas las tiendas).
 */
class PurchaseOrderTrackingService
{
    /** Órdenes que no cuentan como faltantes en el Excel de referencias faltantes. Se quitará más adelante. */
    public const EXCLUDED_DOC_NAMES = [
        'OC-1-9050', 'OC-1-8994', 'OC-1-9027', 'OC-1-9806', 'OC-1-9808', 'OC-1-9810', 'OC-1-9811', 'OC-1-9802', 'OC-1-9809', 'OC-1-9807', 'OC-1-9813', 'OC-1-9804',
        'OC-1-9803', 'OC-1-9812', 'OC-1-9805', 'OC-1-10752', 'OC-1-11325', 'OC-1-11327', 'OC-1-11318', 'OC-1-11330', 'OC-1-12659', 'OC-1-12397', 'OC-1-10821',
        'OC-1-10816', 'OC-1-10812', 'OC-1-10813', 'OC-1-10819', 'OC-1-10817', 'OC-1-10822', 'OC-1-10823', 'OC-1-12174', 'OC-1-10815', 'OC-1-10832', 'OC-1-10824',
        'OC-1-10825', 'OC-1-10829', 'OC-1-10826', 'OC-1-10830', 'OC-1-10834', 'OC-1-10912', 'OC-1-10911', 'OC-1-10913', 'OC-1-10880', 'OC-1-10907', 'OC-1-10874',
        'OC-1-10873', 'OC-1-10876', 'OC-1-10875', 'OC-1-10910', 'OC-1-10908', 'OC-1-10906', 'OC-1-10905', 'OC-1-10878', 'OC-1-10877', 'OC-1-10904', 'OC-1-10909',
        'OC-1-10914', 'OC-1-10915', 'OC-1-10916', 'OC-1-10879', 'OC-1-9369', 'OC-1-9414', 'OC-1-9476', 'OC-1-9422', 'OC-1-9424', 'OC-1-9443', 'OC-1-9653', 'OC-1-9652',
        'OC-1-9654', 'OC-1-9642', 'OC-1-9650', 'OC-1-9640', 'OC-1-9649', 'OC-1-9660', 'OC-1-9659', 'OC-1-9661', 'OC-1-9658', 'OC-1-9635', 'OC-1-10571', 'OC-1-10574',
        'OC-1-13446', 'OC-1-9502', 'OC-1-9506', 'OC-1-9509',
        'OC-1-9876', 'OC-1-10044', 'OC-1-10043', 'OC-1-10045', 'OC-1-10035', 'OC-1-10039', 'OC-1-10028', 'OC-1-10027', 'OC-1-10030', 'OC-1-10031', 'OC-1-10029',
        'OC-1-10042', 'OC-1-10040', 'OC-1-10038', 'OC-1-10037', 'OC-1-10033', 'OC-1-10032', 'OC-1-10036', 'OC-1-10041', 'OC-1-10046', 'OC-1-10034', 'OC-1-9759',
        'OC-1-9758', 'OC-1-9760', 'OC-1-9754', 'OC-1-9757', 'OC-1-9755', 'OC-1-9753', 'OC-1-9752', 'OC-1-9748', 'OC-1-9751', 'OC-1-9756', 'OC-1-9761', 'OC-1-9749',
        'OC-1-13665', 'OC-1-13658', 'OC-1-13657', 'OC-1-13660', 'OC-1-13659', 'OC-1-13667', 'OC-1-13663', 'OC-1-13661', 'OC-1-13668', 'OC-1-13666', 'OC-1-13664', 'OC-1-13662',
        'OC-1-11589', 'OC-1-11590', 'OC-1-13611', 'OC-1-11600', 'OC-1-11585', 'OC-1-11586', 'OC-1-13615', 'OC-1-12627',
    ];

    public function __construct(
        private SiigoClient $client,
        private SiigoCatalogService $catalog,
        private SiigoPurchaseOrderCacheService $purchaseCache,
        private PurchaseOrderItemsParser $items
    ) {
    }

    /** Órdenes de compra de los últimos 5 meses, solo las que el usuario puede ver. */
    public function documents(User $user): array
    {
        $orders = $this->allDocuments();

        if ($user->hasAccessToAllStores()) {
            return $orders->all();
        }

        return $orders
            ->filter(fn ($order) => collect($order['WarehouseCodes'] ?? [])->contains(fn ($code) => is_numeric($code) && $user->canAccessWarehouse((int) $code)))
            ->values()
            ->all();
    }

    /**
     * Filas del Excel de referencias faltantes (órdenes sin lo pedido recibido, por referencia y color).
     *
     * @return array<int, array<string, mixed>>
     */
    public function missingRows(User $user): array
    {
        ini_set('memory_limit', '-1');

        $purchaseOrders = collect($this->documents($user))->whereNotIn('DocName', self::EXCLUDED_DOC_NAMES);

        return $purchaseOrders->flatMap(function ($order) {

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
    }

    /** Todas las órdenes con su seguimiento, sin filtrar por acceso. */
    private function allDocuments(): Collection
    {
        ini_set('memory_limit', '-1');

        $token = $this->client->token();
        $users = $this->catalog->usersById($token);
        $warehouses = $this->catalog->warehousesById($token);
        $productsByCode = app(SiigoProductsCache::class)->keyedByProductId();

        // Cache: histórico | En vivo: solo hoy
        // 5 meses atrás sin contar el actual (día 1 de ese mes) hasta ayer; igual que siigo:warm-purchase-cache
        $fecha_inicio = Carbon::now()->startOfMonth()->subMonths(5);
        $ayer = Carbon::yesterday();
        $hoy_inicio = Carbon::today();
        $hoy_fin = Carbon::now();

        // Facturas históricas: solo caché. Facturas de hoy: consulta en vivo.
        $invoices_cached = $this->cachedDocumentsByDay('FC', $fecha_inicio, $ayer);
        $invoices_today = $this->purchaseCache->purchaseInvoices($token, $hoy_inicio, $hoy_fin);
        $invoices = $invoices_cached->merge($invoices_today)->unique('ACEntryID')->values();

        $invoice_details = $this->details($token, $invoices->pluck('ACEntryID'), 'FC', $invoices_today->pluck('ACEntryID'));

        $purchase_invoices = $invoices->map(function ($invoice) use ($invoice_details, $users, $warehouses, $productsByCode) {

                $detail = $invoice_details->get($invoice['ACEntryID']);
                $user = $users->get(data_get($detail, 'Entry.SalesmanCode'));
                $invoice['User'] = trim(data_get($user, 'first_name', '') . ' ' . data_get($user, 'last_name', ''));
                $invoice['ACEntryCode'] = data_get($detail, 'Entry.ACEntryCode');

                $invoice['Items'] = collect($this->items->parse($detail, $warehouses))->map(function ($item) use ($productsByCode) {
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

        $order_details = $this->details($token, $orders->pluck('ACEntryID'), 'OC', $orders_today->pluck('ACEntryID'));

        $providerKeys = $orders->pluck('MsThirdPartyID')->filter()->unique()->values()
            ->map(fn ($uuid) => LegacyCacheKeys::provider($uuid))
            ->all();
        $providers = collect(Cache::many($providerKeys));

        return $orders->map(function ($order) use ($order_details, $providers, $purchase_invoices, $users, $warehouses, $productsByCode) {
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

            $items = collect($this->items->parse($detail, $warehouses))->map(function ($item) use ($confirmedByProduct, $productsByCode) {
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

            // Solo en la API: ids de las bodegas de la orden, para filtrar por las tiendas del usuario
            $order['WarehouseCodes'] = collect($detail['Items'] ?? [])
                ->pluck('WarehouseCode')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $order['Link'] = SiigoLinks::purchaseOrder($order['ACEntryID']);

            return $order;
        });
    }

    private function cachedDocumentsByDay(string $type, Carbon $from, Carbon $to): Collection
    {
        $days = collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(fn ($day) => $day->toDateString());

        $keys = $days->map(fn ($day) => LegacyCacheKeys::purchaseDay($type, $day))->all();
        $cached = Cache::many($keys);
        $rows = collect();

        foreach ($days as $day) {
            $key = LegacyCacheKeys::purchaseDay($type, $day);

            if (!array_key_exists($key, $cached) || $cached[$key] === null) {
                continue;
            }

            $rows = $rows->merge($cached[$key]);
        }

        return $rows->unique('ACEntryID')->values();
    }

    /** Detalle de cada documento: del caché compartido (OC-ID-{id} / FC-ID-{id}) y, solo los de hoy, de Siigo. */
    private function details(string $token, $ids, string $type, $fetchMissingIds = []): Collection
    {
        $ids = collect($ids)->filter()->unique()->values();
        $fetchMissingIds = collect($fetchMissingIds)->map(fn ($id) => (string) $id)->flip();
        $cached = Cache::many($ids->map(fn ($id) => LegacyCacheKeys::purchaseDetail($type, $id))->all());

        return $ids->mapWithKeys(function ($id) use ($token, $type, $cached, $fetchMissingIds) {
            $key = LegacyCacheKeys::purchaseDetail($type, $id);

            if (array_key_exists($key, $cached) && $cached[$key] !== null) {
                return [$id => $cached[$key]];
            }

            if ($fetchMissingIds->has((string) $id)) {
                return [$id => $this->purchaseCache->fetchDetail($token, (int) $id, $key)];
            }

            return [$id => []];
        });
    }
}

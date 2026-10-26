<?php

namespace App\Jobs;

use App\Mail\MasivePurchaseOrderProviderSiigo;
use App\Mail\MasivePurchaseOrderSiigo;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoPurchaseOrderMonolith;
use App\Services\Siigo\SiigoPurchaseOrderPayload;
use App\Services\Siigo\SiigoRetryPolicy;
use App\Services\SiigoPurchaseOCPdfService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class ImportMasivePurchaseOrderSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DISK = 'public';
    private const BASE_PATH = 'products';

    /**
     * El proceso hace varias llamadas HTTP a Siigo + sleep(45) entre
     * órdenes, así que le damos bastante margen.
     */
    public int $timeout = 7200;
    public int $tries = 1;
    private array $ordenes_compra = [];

    public function __construct(public Collection $order, public string $email, public string $referencia)
    {
        $this->onQueue('masive-purchase-orders');
    }

    public function handle(): void
    {
        $this->procesar();

        // Los PDF solo existen para adjuntarlos a los correos: ya enviados, se borran.
        // Si procesar() lanza una excepcion, la limpieza la hace failed() (tries = 1).
        $this->eliminar_archivos_temporales();
    }

    private function procesar(): void
    {
        ini_set('serialize_precision', '-1');
        $errors = [];

        $this->referencia = $this->sanitize_referencia($this->referencia);
        $path = self::BASE_PATH . "/{$this->referencia}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            $this->notificarResultado([[
                'Row' => 'FOTO',
                'Error' => 'La(s) foto(s) es obligatorio.',
            ]]);
            return;
        }

        $files = $this->obtener_archivos($this->referencia);

        $config = $this->order['orden_compra'][0]->toArray();

        $cookie = $config['cookie'];

        if (empty($cookie)) {
            $this->notificarResultado([[
                'Row' => 'COOKIE',
                'Error' => 'La cookie es obligatorio',
            ]]);
            return;
        }

        $token = $this->monolith()->tokenFromCookie($cookie);

        if (empty($token)) {
            $this->notificarResultado([[
                'Row' => 'COOKIE',
                'Error' => 'La cookie no trae el token TKNSGRDDREVENTCALZADOSAS. Copia la cookie completa de Siigo',
            ]]);
            return;
        }

        $purchase_order_type_id = $config['tipo'];

        [$user, $validate] = $this->obtener_datos_usuario($token);
        if (!empty($validate)) {
            $this->notificarResultado($validate);
            return;
        }

        if (empty($purchase_order_type_id)) {
            $this->notificarResultado([[
                'Row' => 'TIPO',
                'Error' => 'El tipo de orden de compra es obligatorio',
            ]]);
            return;
        }

        $purchase_order_type = $this->monolith()->purchaseOrderType($token, $cookie, $purchase_order_type_id);

        if (empty($purchase_order_type)) {
            $this->notificarResultado([[
                'Row' => 'TIPO',
                'Error' => 'El tipo de orden de compra no es valido',
            ]]);
            return;
        }

        if (isset($config['fecha']) && $config['fecha'] != '') {
            $fecha = $config['fecha'];
            if (is_numeric($fecha)) {
                $config['fecha'] = Date::excelToDateTimeObject($fecha)->format('Y-m-d');
            } else {
                $config['fecha'] = Carbon::createFromFormat('d/m/Y', trim($fecha))->format('Y-m-d');
            }

            if (Carbon::parse($config['fecha'])->gt(Carbon::today())) {
                $errors[] = [
                    'Row' => 'FECHA',
                    'Error' => 'La fecha no puede ser mayor a la fecha actual',
                ];
            }
        } else {
            $errors[] = [
                'Row' => 'FECHA',
                'Error' => 'La fecha es obligatoria',
            ];
        }

        if (isset($config['fecha_limite']) && $config['fecha_limite'] != '') {
            $fecha_limite = $config['fecha_limite'];
            if (is_numeric($fecha_limite)) {
                $config['fecha_limite'] = Date::excelToDateTimeObject($fecha_limite)->format('Y-m-d');
            } else {
                $config['fecha_limite'] = Carbon::createFromFormat('d/m/Y', trim($fecha_limite))->format('Y-m-d');
            }

            if (Carbon::parse($config['fecha_limite'])->lt(Carbon::parse($config['fecha']))) {
                $errors[] = [
                    'Row' => 'FECHA LIMITE',
                    'Error' => 'La fecha limite no puede ser menor a la fecha',
                ];
            }
        } else {
            $errors[] = [
                'Row' => 'FECHA LIMITE',
                'Error' => 'La fecha limite es obligatoria',
            ];
        }

        $cost_center = [];

        if ($purchase_order_type->UseCostCenter) {
            if (empty($config['centro_costo'])) {
                $errors[] = [
                    'Row' => 'CENTRO COSTO',
                    'Error' => 'El centro de costo es obligatorio',
                ];
            } else {
                $cost_centers = collect($this->monolith()->costCenters())->keyBy('id')->all();

                if (!isset($cost_centers[$config['centro_costo']])) {
                    $errors[] = [
                        'Row' => 'CENTRO COSTO',
                        'Error' => 'El centro de costo no es valido',
                    ];
                } else {
                    $cost_center = $cost_centers[$config['centro_costo']];
                }
            }
        }

        $list_taxes = $this->monolith()->listTaxes($token, $cookie);

        if ($purchase_order_type->IsReteIva) {
            if (!isset($config['rete_iva']) || $config['rete_iva'] === '') {
                $errors[] = [
                    'Row' => 'RETE IVA',
                    'Error' => 'El rete iva es obligatorio',
                ];
            } elseif (!collect($list_taxes['rete_iva'] ?? [])->contains('Id', $config['rete_iva'])) {
                $errors[] = [
                    'Row' => 'RETE IVA',
                    'Error' => 'El rete iva no es valido',
                ];
            }
        }

        if ($purchase_order_type->IsReteIca) {
            if (!isset($config['rete_ica']) || $config['rete_ica'] === '') {
                $errors[] = [
                    'Row' => 'RETE ICA',
                    'Error' => 'El rete ica es obligatorio',
                ];
            } elseif (!collect($list_taxes['rete_ica'] ?? [])->contains('Id', $config['rete_ica'])) {
                $errors[] = [
                    'Row' => 'RETE ICA',
                    'Error' => 'El rete ica no es valido',
                ];
            }
        }

        if (!empty($errors)) {
            $this->notificarResultado($errors);
            return;
        }

        if (empty($config['proveedor'])) {
            $this->notificarResultado([[
                'Row' => 'PROVEEDOR',
                'Error' => 'El proveedor es obligatorio',
            ]]);
            return;
        }

        $search_providers = $this->monolith()->searchProviders($token, $cookie, $config['proveedor']);

        if ($search_providers->count() > 1) {
            $this->notificarResultado([[
                'Row' => 'PROVEEDOR',
                'Error' => 'El proveedor no es valido. Multiples registros de busqueda',
            ]]);
            return;
        } elseif ($search_providers->isEmpty()) {
            $this->notificarResultado([[
                'Row' => 'PROVEEDOR',
                'Error' => 'El proveedor no es valido',
            ]]);
            return;
        }

        $search_providers = $search_providers->first();
        $provider = $this->monolith()->searchProvider($token, $cookie, $search_providers['ID']);

        $warehouses = $this->monolith()->warehouses();
        $warehouses = collect($warehouses)->keyBy('id')->all();

        $fecha = Carbon::createFromFormat('Y-m-d', $config['fecha'])->format('Ymd');

        [$orden_compra_detalles, $errors] = $this->validar_detalles($token, $cookie, $this->order['orden_compra_detalles']->toArray(), $warehouses, $fecha, $list_taxes['imp_cargo'], $list_taxes['imp_retencion'], $user);

        if (!empty($errors)) {
            $this->notificarResultado($errors);
            return;
        }

        $orden_compra_detalles = collect($orden_compra_detalles)->groupBy('Warehouse_Id');

        foreach ($orden_compra_detalles as $warehouse_id => $detalles) {
            $warehouse = $warehouses[$warehouse_id];

            $detalles_agrupados = $detalles->groupBy(function ($detalle) {
                if ($detalle['TaxDiscount_Id'] == -1 && $detalle['TaxAdd_Id'] == -1) return 'REMISION';

                return 'IVA';
            });

            foreach ($detalles_agrupados as $tipo => $detalles_tipo) {

                $body = app(SiigoPurchaseOrderPayload::class)->build($config, $user, $purchase_order_type, $list_taxes['rete_iva'], $list_taxes['rete_ica'], $provider, $cost_center, $fecha, $detalles_tipo, $warehouse, $tipo);

                [$orden_compra, $validate] = $this->orden_compra($token, $cookie, $body, $warehouse, $tipo);

                if (!empty($validate)) {
                    $errors = array_merge($errors, $validate);
                    continue;
                }

                $info = $this->consultar_orden_compra($token, $cookie, $orden_compra['documento_id'], $orden_compra['url']);

                $this->ordenes_compra[] = [
                    'bodega' => $warehouse,
                    'tipo' => $tipo,
                    'documento' => $info['documento'],
                    'url' => $info['url'],
                    'pdf' => $this->generar_pdf_orden_compra($cookie, $orden_compra['documento_id'], $info['documento']),
                ];

                // Siigo rechaza como duplicado dos creaciones seguidas: se espera entre órdenes.
                app(SiigoRetryPolicy::class)->pauseBetweenDocuments();
            }
        }

        $this->notificarResultado($errors, $this->ordenes_compra);
        if(count($this->ordenes_compra) > 0) {
            Mail::to([$this->email, config('siigo.mail.operations')])->send(new MasivePurchaseOrderProviderSiigo($this->ordenes_compra, $files, $this->referencia, $provider));
        }
    }

    private function notificarResultado(array $errors, array $ordenes_compra = []): void
    {
        Mail::to([config('siigo.mail.operations')])->send(new MasivePurchaseOrderSiigo($ordenes_compra, $errors));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportMasivePurchaseOrderSiigoJob falló', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);

        try {
            Mail::to([config('siigo.mail.operations')])->send(new MasivePurchaseOrderSiigo(
                ordenes_compra: $this->ordenes_compra,
                errors: [
                    [
                        'Row'   => 'ERROR DESCONOCIDO',
                        'Error' => 'Ocurrió un error inesperado procesando la orden de compra: ' . $exception->getMessage() . '. Revisar hasta que bodega se creo.',
                    ],
                ]
            ));
        } finally {
            $this->eliminar_archivos_temporales();
        }
    }

    /** Borra los PDF temporales de las órdenes creadas (no toca las fotos de la referencia). */
    private function eliminar_archivos_temporales(): void
    {
        foreach ($this->ordenes_compra as $orden) {
            $pdf = $orden['pdf'] ?? null;

            if (!empty($pdf['disk']) && !empty($pdf['path'])) {
                Storage::disk($pdf['disk'])->delete($pdf['path']);
            }
        }
    }

    /**
     * Genera el mismo PDF de la ruta siigo/search_document/OC/pdf y lo guarda para adjuntarlo en los correos.
     * Si falla no detiene el proceso (la orden ya quedó creada en Siigo): el correo sale sin el PDF.
     */
    private function generar_pdf_orden_compra(string $cookie, int|string|null $erp_document_id, ?string $documento): ?array
    {
        if (empty($erp_document_id)) {
            return null;
        }

        try {
            return app(SiigoPurchaseOCPdfService::class)->store_purchase_order_pdf($cookie, $erp_document_id, $documento);
        } catch (\Throwable $e) {
            Log::warning('No se pudo generar el PDF de la orden de compra', [
                'erp_document_id' => $erp_document_id,
                'documento' => $documento,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function obtener_archivos(string $referencia): Collection
    {
        $path = self::BASE_PATH . "/{$referencia}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            return collect();
        }

        return collect(Storage::disk(self::DISK)->allFiles($path))
            ->sortBy(fn ($file) => [
                substr_count($file, '/'), // primero las de la raíz, luego las de subcarpetas
                $file,
            ])
            ->map(fn ($file) => [
                'name' => basename($file),
                'url' => Storage::disk(self::DISK)->url($file),
            ])
            ->values();
    }

    private function consultar_orden_compra(string $token, string $cookie, int|string $erp_document_id, string $urlDefault, int $intento = 1): array
    {
        $retry = app(SiigoRetryPolicy::class);
        $maxIntentos = $retry->consultMaxAttempts();
        $segundosEspera = $retry->consultWaitSeconds($intento);

        try {
            $response = $this->monolith()->orderPage($token, $cookie, $erp_document_id);

            if (!$response->successful()) {
                if ($intento < $maxIntentos) {
                    Log::warning('consultar_orden_compra reintentando por respuesta no exitosa', [
                        'erp_document_id' => $erp_document_id,
                        'intento' => $intento,
                        'espera' => $segundosEspera,
                    ]);

                    sleep($segundosEspera);

                    return $this->consultar_orden_compra($token, $cookie, $erp_document_id, $urlDefault, $intento + 1);
                }

                return [
                    'documento' => '-',
                    'url' => $urlDefault,
                ];
            }

            $crawler = new Crawler($response->body());

            $documento = null;

            $titleSpan = $crawler->filter('#Default_ucControlPane0_ContainerTitle');

            if ($titleSpan->count()) {
                $documento = trim(Str::after($titleSpan->text(), ':'));
            }

            $url = null;

            $btnCopyUrl = $crawler->filter('#Default_ucControlPane0_ctl00_btnCopyUrl');

            if ($btnCopyUrl->count()) {
                $onclick = $btnCopyUrl->attr('onclick');

                if ($onclick) {
                    preg_match('/setClipboardText\(["\'](.*?)["\']\)/', $onclick, $matches);

                    if (isset($matches[1])) {
                        $url = html_entity_decode($matches[1]);
                    }
                }
            }

            if (($documento === null || $url === null) && $intento < $maxIntentos) {
                return $this->consultar_orden_compra($token, $cookie, $erp_document_id, $urlDefault, $intento + 1);
            }

            return [
                'documento' => $documento,
                'url' => $url,
            ];

        } catch (ConnectionException $e) {
            if ($intento < $maxIntentos) {
                Log::warning('consultar_orden_compra reintentando por ConnectionException', [
                    'erp_document_id' => $erp_document_id,
                    'intento' => $intento,
                    'espera' => $segundosEspera,
                ]);

                sleep($segundosEspera);

                return $this->consultar_orden_compra($token, $cookie, $erp_document_id, $urlDefault, $intento + 1);
            }

            return [
                'documento' => '-',
                'url' => $urlDefault,
            ];
        }
    }

    private function orden_compra(string $token, string $cookie, array $body, array $warehouse, string $tipo, int $intento = 1)
    {
        $retry = app(SiigoRetryPolicy::class);

        $response = $this->monolith()->createOrder($token, $cookie, $body);

        if (!$response->successful()) {
            Log::error('ImportMasivePurchaseOrderSiigoJob falló', [
                'error' => $response,
                'intento' => $intento,
            ]);

            $validate = [
                [
                    'Row'   => "ERROR SIIGO - ORDEN DE COMPRA: {$warehouse['id']} - {$warehouse['name']} {$tipo}",
                    'Error' => "No se pudo realizar la carga debido a problemas con Siigo. Los detalles de esta bodega y tipo ({$tipo}) deben ser separados para realizar nuevamente la carga. Error de Siigo: " . $response->body(),
                ]
            ];

            return [[], $validate];
        }

        $data = $this->parse_siigo_response($response->body());

        if (empty($data['success']) || $data['success'] !== true) {
            Log::error('ImportMasivePurchaseOrderSiigoJob falló', [
                'error' => $response,
                'intento' => $intento,
            ]);

            // Falso duplicado: Siigo lo marca si recibe dos creaciones en el mismo segundo.
            if ($retry->shouldRetryDuplicate($data['msg'] ?? null, $intento)) {
                Log::warning('ImportMasivePurchaseOrderSiigoJob reintentando por posible duplicidad', [
                    'warehouse' => $warehouse,
                    'tipo' => $tipo,
                    'intento' => $intento,
                ]);

                $retry->waitBeforeDuplicateRetry();

                return $this->orden_compra($token, $cookie, $body, $warehouse, $tipo, $intento + 1);
            }

            $intentosTexto = $retry->attemptsText($intento, 'la advertencia persistió.');

            $validate = [
                [
                    'Row'   => "ADVERTENCIA SIIGO - ORDEN DE COMPRA: {$warehouse['id']} - {$warehouse['name']} {$tipo}",
                    'Error' => "La respuesta recibida de Siigo no fue la esperada. {$intentosTexto} Verifique directamente en Siigo si el documento fue creado antes de intentar realizar nuevamente la carga. Respuesta de Siigo: " . ($data['msg'] ?? 'Error desconocido al crear la orden de compra'),
                ]
            ];

            return [[], $validate];
        }

        return [['documento_id' => $data['erpDocumentID'] ?? null, 'url' => $this->monolith()->absoluteUrl($data['url'])], []];
    }

    private function parse_siigo_response(string $body): array
    {
        // Convierte { success:true, url: 'algo', erpDocumentID:2515553 } en JSON válido
        $json = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $body);
        $json = preg_replace("/:\s*'([^']*)'/", ': "$1"', $json);

        $data = json_decode($json, true);

        return $data ?? [];
    }

    private function validar_detalles(string $token, string $cookie, array $orden_compra_detalles, array $warehouses, string $date, array $imp_cargo, array $imp_retencion, array $user)
    {
        $validate = [];
        $detalles = [];
        $productCache = [];

        $imp_cargo = collect($imp_cargo)->keyBy('Id')->all();
        $imp_retencion = collect($imp_retencion)->keyBy('Id')->all();

        foreach ($orden_compra_detalles as $row => $detalle) {
            $warehouse = $warehouses[$detalle['bodega']] ?? [];

            if (!isset($detalle['tipo_detalle']) || $detalle['tipo_detalle'] === '') {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El tipo es obligatorio',
                ];
            } elseif (!in_array($detalle['tipo_detalle'], ['0', '1', '2'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El tipo no es valido',
                ];
            }

            if (empty($detalle['item'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El item es obligatorio',
                ];
            }

            $product = [];

            if (!empty($detalle['item'])) {
                if (array_key_exists($detalle['item'], $productCache)) {
                    // Ya se buscó este item en una fila anterior, se reutiliza
                    $product = $productCache[$detalle['item']];
                } else {
                    $search_product = $this->monolith()->searchProducts($token, $cookie, $detalle['item']);

                    if (empty($search_product)) {
                        $validate[] = [
                            'Row' => $row + 1,
                            'ProductCode' => $detalle['item'],
                            'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                            'Error' => 'El item no es valido',
                        ];
                    } else {
                        $product = $this->monolith()->searchProduct($token, $cookie, $search_product['ID'], $date);
                    }

                    $productCache[$detalle['item']] = $product;
                }
            }

            if (empty($product)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El producto no existe',
                ];
            }

            if (empty($detalle['bodega'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La bodega es obligatorio',
                ];
            } elseif (empty($warehouse)) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La bodega no es valida',
                ];
            }

            if (empty($detalle['cantidad'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La cantidad es obligatorio',
                ];
            } elseif (!is_numeric($detalle['cantidad'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La cantidad debe ser un valor numerico',
                ];
            } elseif ($detalle['cantidad'] == 0) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La cantidad debe ser mayor a 0',
                ];
            }

            if (empty($detalle['valor_unitario'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El valor unitario es obligatorio',
                ];
            } elseif (!is_numeric($detalle['valor_unitario'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El valor unitario debe ser un valor numerico',
                ];
            }

            $detalle['descuento'] = $detalle['descuento'] ?: 0;

            if (!is_numeric($detalle['descuento'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El descuento debe ser un valor numerico',
                ];
            } elseif (is_numeric($detalle['valor_unitario']) && is_numeric($detalle['descuento'])) {
                if ($detalle['descuento'] > $detalle['valor_unitario']) {
                    $validate[] = [
                        'Row' => $row + 1,
                        'ProductCode' => $detalle['item'],
                        'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                        'Error' => 'El descuento no puede ser mayor al precio unitario',
                    ];
                }
            }

            if (empty($detalle['imp_cargo'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto cargo es obligatorio',
                ];
            } elseif (!isset($imp_cargo[$detalle['imp_cargo']])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto cargo no es valido',
                ];
            }

            if (empty($detalle['imp_retencion'])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto retencion es obligatorio',
                ];
            } elseif (!isset($imp_retencion[$detalle['imp_retencion']])) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'El impuesto retencion no es valido',
                ];
            }

            $pWarehouseList = is_string($product['pWarehouseList'] ?? null) ? json_decode($product['pWarehouseList'], true) : ($product['pWarehouseList'] ?? []);
            $warehouseEntry = collect($pWarehouseList)->firstWhere('id', $detalle['bodega']);

            if (!$warehouseEntry) {
                $validate[] = [
                    'Row' => $row + 1,
                    'ProductCode' => $detalle['item'],
                    'WarehouseCode' => $detalle['bodega'] . ' - ' . ($warehouse['name'] ?? '#N/A'),
                    'Error' => 'La bodega no existe para este producto',
                ];
            }

            if (!empty($validate)) continue;

            $pPriceList = is_string($product['pPriceList'] ?? null) ? json_decode($product['pPriceList'], true) : ($product['pPriceList'] ?? []);
            $defaultMoneyList = collect($pPriceList)->firstWhere('MoneyCode', $product['defaultMoneyCode'] ?? 'COP');
            $priceList = $defaultMoneyList['PriceList'] ?? [];

            $TaxAdd = $imp_cargo[$detalle['imp_cargo']] ?? [];
            $TaxDisc = $imp_retencion[$detalle['imp_retencion']] ?? [];

            $grossValue = round($detalle['valor_unitario'] * $detalle['cantidad'], 2);
            $baseValue = round($grossValue - ($detalle['descuento'] ?: 0), 2);

            $add = $this->calculate_tax_add($detalle['valor_unitario'], $detalle['cantidad'], $detalle['descuento'], $TaxAdd);
            $disc = $this->calculate_tax_discount($detalle['valor_unitario'], $detalle['cantidad'], $detalle['descuento'], $TaxDisc);

            $totalValue = round($baseValue - $disc['TaxDiscount_Value'] + $add['TaxAdd_Value'], 2);
            $valueWithChargeTaxes = round($baseValue + $add['TaxAdd_Value'], 2);

            $pWarehouseList = json_decode($product['pWarehouseList']);

            $detalles[] = [
                "rowID" => $row + 1,
                "SelectedValue" => $product["pProductID"] ?: "",
                "SelectedCode" => $product["pCode"] ?: "",
                "SelectedName" => $product["pName"] ?: "",
                "Description" => $product["pDescription"] ?: "",
                "GrossValue" => $grossValue,
                "Quantity" => $detalle['cantidad'],
                "UndValue" => $detalle['valor_unitario'],
                "UndReaValue" => $detalle['valor_unitario'],
                "Discount" => $detalle['descuento'] ?: 0,
                "Discount_Value" => $detalle['descuento'] ?: 0,
                "BaseValue" => $baseValue,
                "IsAIU" => false,
                "AIU" => 0,
                "BaseAIU" => 0,
                "ProductSubType" => $product['pSubType'] ?: 0,
                "TaxDiscount_Name" => $disc['TaxDiscount_Name'],
                "TaxDiscount_Id" => $disc['TaxDiscount_Id'],
                "TaxDiscount_Value" => $disc['TaxDiscount_Value'],
                "TaxDiscount_Percentage" => $disc['TaxDiscount_Percentage'],
                "TotalValue" => $totalValue,
                "priceList" => $priceList,
                "Warehouse_Id" => $warehouseEntry['id'] ?? "-1",
                "Warehouse_Description" => $warehouseEntry['description'] ?? "",
                "Warehouse_Value" => $warehouseEntry['value'] ?? "0.00",
                "Warehouse_Code" => $warehouseEntry['code'] ?? "",
                "SalesmanCode" => "",
                "SalesmanName" => "",
                "SalesmanID" => $user['id'],
                "AccountID" => -1,
                "AccountCode" => "",
                "AccountName" => "",
                "ProductIsInventoryControl" => $product['pIsInventoryControl'] ?? false,
                "IsInventoryControl" => $product['pIsInventoryControl'] ?? false,
                "TaxAdd_Name" => $add['TaxAdd_Name'],
                "TaxAdd_Id" => $add['TaxAdd_Id'],
                "TaxAdd_SubType" => $add['TaxAdd_SubType'],
                "TaxAdd_Value" => $add['TaxAdd_Value'],
                "TaxAdd_Percentage" => $add['TaxAdd_Percentage'],
                "TaxImpoValue" => 0,
                "TaxAdd2_Value" => 0,
                "ConsumptionValue" => 0,
                "ValueWithChargeTaxes" => $valueWithChargeTaxes,
                "ItemType" => $detalle['tipo_detalle'],
                "pWarehouseList" => json_encode([]/*$pWarehouseList*/)
            ];
        }

        return [$detalles, $validate];
    }

    private function calculate_tax_add(int|float $value, int $quantity, int|float $discount, array $tax)
    {
        if (empty($tax) || $tax['Id'] == -1) {
            return [
                "TaxAdd_Name" => "",
                "TaxAdd_Id" => -1,
                "TaxAdd_SubType" => -1,
                "TaxAdd_Value" => 0,
                "TaxAdd_Percentage" => 0,
                "TaxAdd2_Value" => 0,
                "Value" => round($value * $quantity, 2),
            ];
        }

        $gross = $value * $quantity;
        $baseValue = $gross - $discount;
        $percentage = $tax['Value'];
        $taxValue = round($baseValue * ($percentage / 100), 2);

        return [
            "TaxAdd_Name" => $tax['Name'],
            "TaxAdd_Id" => $tax['Id'],
            "TaxAdd_SubType" => $tax['SubType'],
            "TaxAdd_Value" => $taxValue,
            "TaxAdd_Percentage" => $percentage,
            "TaxAdd2_Value" => 0,
            "Value" => round($baseValue, 2) + $taxValue,
        ];
    }

    private function calculate_tax_discount(int|float $value, int $quantity, int|float $discount, array $tax)
    {
        if (empty($tax) || $tax['Id'] == -1) {
            return [
                "TaxDiscount_Name" => "",
                "TaxDiscount_Id" => -1,
                "TaxDiscount_Value" => 0,
                "TaxDiscount_Percentage" => 0,
            ];
        }

        $gross = $value * $quantity;
        $baseValue = $gross - $discount;
        $percentage = $tax['Value'];

        return [
            "TaxDiscount_Name" => $tax['Name'],
            "TaxDiscount_Id" => $tax['Id'],
            "TaxDiscount_Value" => round($baseValue * ($percentage / 100), 2),
            "TaxDiscount_Percentage" => $percentage,
        ];
    }

    private function obtener_datos_usuario(string $token)
    {
        $response = app(SiigoClient::class)->userSettings($token);

        if (!$response->successful()) {
            return [[], [[
                'Row'   => 'TOKEN',
                'Error' => 'El token no sirve o expiró, genera uno nuevo',
            ]]];
        }

        $data = $response->json();

        return [
            [
                'id' => $data['userID'],
                'name' => $data['userName'],
            ],
            []
        ];
    }

    private function monolith(): SiigoPurchaseOrderMonolith
    {
        return app(SiigoPurchaseOrderMonolith::class);
    }

    private function sanitize_referencia(string $referencia): string
    {
        $referencia = trim($referencia);

        if (class_exists(\Normalizer::class)) {
            $referencia = \Normalizer::normalize($referencia, \Normalizer::FORM_C) ?: $referencia;
        }

        $limpia = preg_replace('/[^A-Za-zÑñ0-9\-_]/u', '-', $referencia);

        return mb_strtoupper($limpia ?? '');
    }
}

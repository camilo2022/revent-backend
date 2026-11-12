<?php

namespace App\Jobs\Integration;

use App\Exports\MasiveTransferSiigoMultiSheetExport;
use App\Mail\MasiveTransferSiigoMail;
use App\Models\Store;
use App\Models\User;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoLinks;
use App\Services\Siigo\SiigoRetryPolicy;
use App\Services\SiigoTransferNTPdfService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Traslados masivos para la API. Misma lógica que App\Jobs\ImportMasiveTransferSiigoJob (web, que sigue igual),
 * pero las bodegas salen de la tabla stores: el permiso es por las tiendas asignadas al usuario logueado,
 * el tránsito es stores.siigo_transit_warehouse_id y los avisos van a stores.email.
 */
class ImportMasiveTransferSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;

    // Rutas (relativas al host de servicios definido en config/siigo.php).
    private const PATH_TRANSFER_SAVE = '/ACEntryApi/api/v1/WarehouseTransfer/Save/';
    private const PATH_TRANSFER_VIEW = '/ACEntryApi/api/v1/WarehouseTransfer/GetDataView';

    /** Disco privado y carpeta temporal de los archivos que solo viajan adjuntos en el correo. */
    private const TMP_DISK = 'local';
    private const TMP_PATH = 'tmp/siigo';

    private const PAGINAS_BUSQUEDA_PRODUCTO = 3;

    private const VISTA_SALIDA = 'email.masive_transfer.exit';
    private const VISTA_ENTRADA = 'email.masive_transfer.entrance';

    /** $userId: usuario logueado del ERP que ejecuta el traslado. */
    public function __construct(private Collection $transfer, private string $email, private int $userId)
    {
        $this->onQueue('masive-transfers');
    }

    /** Traslados creados en esta ejecución; sus archivos temporales se borran siempre al terminar. */
    private array $traslados_generados = [];

    /** Tiendas con bodega de Siigo, cargadas una sola vez por ejecución. */
    private ?Collection $tiendas = null;

    public function handle(): void
    {
        try {
            $this->procesar();
        } finally {
            // Los archivos (PDF y Excel) solo existen para adjuntarlos al correo: se borran aunque algo falle.
            $this->eliminar_archivos_temporales();
        }
    }

    private function procesar(): void
    {
        $traslados = [];

        [$config, $errors] = $this->validar_configuracion($this->transfer['traslado']->first()?->toArray() ?? []);

        if (!empty($errors)) {
            $this->notificar_resultado($traslados, $errors);
            return;
        }

        $token = $config['token'];

        // El token solo se valida para poder ejecutar en Siigo; el permiso lo da el usuario del ERP.
        [, $errors] = $this->obtener_datos_usuario($token);

        if (!empty($errors)) {
            $this->notificar_resultado($traslados, $errors);
            return;
        }

        $user = User::find($this->userId);

        if (!$user) {
            $this->notificar_resultado($traslados, [$this->error_general('USUARIO', 'El usuario que ejecuta el traslado no existe')]);
            return;
        }

        $detalles = $this->agrupar_duplicados($this->transfer['traslado_detalles']->toArray());

        $errors = array_merge($errors, $this->validar_permiso_bodega($detalles, $user));

        [$detalles, $validate] = $this->buscar_producto($token, $detalles);
        $errors = array_merge($errors, $validate);

        $errors = array_merge($errors, $this->validar_bodega_salida($token, $detalles, $config));
        $errors = array_merge($errors, $this->validar_bodega_entrada($token, $detalles, $config));

        if ($config['tipo'] == 'TRANSITO') {
            [$detalles, $validate] = $this->validar_bodega_transito($detalles);
            $errors = array_merge($errors, $validate);
        }

        if (empty($errors)) {
            foreach ($this->separar_traslados($detalles) as $grupo) {
                [$traslado, $validate] = $this->traslado($token, $grupo, $config);

                if (!empty($validate)) {
                    $errors = array_merge($errors, $validate);
                    continue;
                }

                $traslados[] = $traslado;
                $this->traslados_generados[] = $traslado;

                $this->notificar_bodega($traslado['data']['bodega_salida_data'], $traslado, self::VISTA_SALIDA);
                $this->notificar_bodega($traslado['data']['bodega_ingreso_data'], $traslado, self::VISTA_ENTRADA, adjuntar_excel: true);

                // Siigo marca como duplicado dos creaciones seguidas: se espera antes del siguiente traslado.
                app(SiigoRetryPolicy::class)->pauseBetweenDocuments();
            }
        }

        $this->notificar_resultado($traslados, $errors);
    }

    /** Borra los PDF y Excel temporales de todos los traslados creados en esta ejecución. */
    private function eliminar_archivos_temporales(): void
    {
        foreach ($this->traslados_generados as $traslado) {
            foreach ([$traslado['pdf'] ?? null, $traslado['approved'] ?? null] as $archivo) {
                if (!empty($archivo['disk']) && !empty($archivo['path'])) {
                    Storage::disk($archivo['disk'])->delete($archivo['path']);
                }
            }
        }

        $this->traslados_generados = [];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportMasiveTransferSiigoJob falló', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);

        $this->notificar_resultado([], [
            $this->error_general('ERROR DESCONOCIDO', 'Ocurrió un error inesperado procesando el traslado masivo: ' . $exception->getMessage()),
        ]);
    }

    /* -----------------------------------------------------------------
     | Notificaciones
     | ----------------------------------------------------------------- */

    private function notificar_resultado(array $traslados, array $errors): void
    {
        Mail::to([$this->email])->send(new MasiveTransferSiigoMail(
            traslados: $traslados,
            errors: $errors,
            adjuntar_pdf: true,
            adjuntar_excel: true,
        ));
    }

    private function notificar_bodega(?array $bodega, array $traslado, string $vista, bool $adjuntar_excel = false): void
    {
        $emails = $bodega['emails'] ?? [];

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(new MasiveTransferSiigoMail(
            traslados: [$traslado],
            template_view: $vista,
            adjuntar_pdf: true,
            adjuntar_excel: $adjuntar_excel,
        ));
    }

    /* -----------------------------------------------------------------
     | Validación de la hoja "traslado"
     | ----------------------------------------------------------------- */

    private function validar_configuracion(array $config): array
    {
        $errors = [];

        if (empty($config['token'] ?? null)) {
            $errors[] = $this->error_general('TOKEN', 'El token es obligatorio');
        }

        if (isset($config['fecha']) && $config['fecha'] != '') {
            $config['fecha'] = $this->normalizar_fecha($config['fecha']);

            if (Carbon::parse($config['fecha'])->gt(Carbon::today())) {
                $errors[] = $this->error_general('FECHA', 'La fecha no puede ser mayor a la fecha actual');
            }
        } else {
            $errors[] = $this->error_general('FECHA', 'La fecha es obligatoria');
        }

        if (!in_array($config['validar_disponible'] ?? null, ['SI', 'NO'], true)) {
            $errors[] = $this->error_general('VALIDAR DISPONIBLE', 'El campo validar disponible debe ser SI o NO');
        }

        if (!in_array($config['tipo'] ?? null, ['DIRECTO', 'TRANSITO'], true)) {
            $errors[] = $this->error_general('TIPO', 'El tipo de traslado debe ser DIRECTO o TRANSITO');
        }

        return [$config, $errors];
    }

    private function normalizar_fecha($fecha): string
    {
        return is_numeric($fecha)
            ? Date::excelToDateTimeObject($fecha)->format('Y-m-d')
            : Carbon::parse($fecha)->format('Y-m-d');
    }

    /* -----------------------------------------------------------------
     | Hoja "traslado_detalles"
     | ----------------------------------------------------------------- */

    private function agrupar_duplicados(array $detalles): array
    {
        return collect($detalles)
            ->groupBy(fn ($item) => $item['codigo'] . '|' . $item['bodega_salida'] . '|' . $item['bodega_entrada'])
            ->map(function ($items) {
                $detalle = $items->first();
                $detalle['cantidad'] = $items->sum('cantidad');

                return $detalle;
            })
            ->values()
            ->all();
    }

    /** La bodega de salida debe ser de una tienda asignada al usuario (o tener acceso a todas). */
    private function validar_permiso_bodega(array $detalles, User $user): array
    {
        $validate = [];

        foreach ($detalles as $row => $detalle) {
            $bodega = $this->bodega($detalle['bodega_salida']);

            if (!empty($bodega) && $user->canAccessWarehouse((int) $detalle['bodega_salida'])) {
                continue;
            }

            $validate[] = [
                'Row'           => $row + 1,
                'ProductCode'   => $detalle['codigo'],
                'Description'   => $detalle['codigo'],
                'WarehouseCode' => $detalle['bodega_salida'] . ' - ' . ($bodega['name'] ?? '#N/A'),
                'Error'         => 'No tiene acceso a la bodega de salida',
            ];
        }

        return $validate;
    }

    private function buscar_producto(string $token, array $detalles): array
    {
        $items = [];
        $validate = [];
        $productos = [];

        foreach ($detalles as $row => $detalle) {
            $codigo = $detalle['codigo'];

            if (empty($codigo)) {
                $validate[] = ['Row' => $row + 1, 'ProductCode' => $codigo, 'Error' => 'El codigo es requerido'];
                continue;
            }

            if (!array_key_exists($codigo, $productos)) {
                [$ok, $resultado] = $this->buscar_con_paginacion($token, (string) $codigo);

                if (!$ok) {
                    return [[], [$this->error_general('ERROR DESCONOCIDO', $resultado)]];
                }

                $productos[$codigo] = $resultado;
            }

            $producto = $productos[$codigo];

            if (!$producto) {
                Log::warning('Traslado masivo: producto no encontrado en Siigo', [
                    'codigo'  => $codigo,
                    'fila'    => $row + 1,
                    'filtro'  => 'IsInventoryControl=1',
                    'paginas' => self::PAGINAS_BUSQUEDA_PRODUCTO,
                ]);
                $validate[] = ['Row' => $row + 1, 'ProductCode' => $codigo, 'Error' => 'El producto no existe o no tiene control de inventario'];
                continue;
            }

            $items[] = [
                'ProductCode'            => $producto['ProductID'],
                'Code'                   => $producto['Code'],
                'Description'            => $producto['Description'],
                'LongDescription'        => $producto['Description'],
                'ProductSubType'         => $producto['ProductType'],
                'ProductUnitMeasurement' => $producto['MeasurementUnit'],
                'EntryItemType'          => $producto['ProductType'],
                'Order'                  => 1,
                'CostCenterCode'         => null,
                'WarehouseCode'          => $detalle['bodega_salida'],
                'DestinationWarehouseCode' => $detalle['bodega_entrada'],
                'AccountCode'            => 0,
                'SalesmanCode'           => null,
                'ConceptCode'            => null,
                'Quantity'               => $detalle['cantidad'],
                'Value'                  => 0,
            ];
        }

        return [$items, $validate];
    }

    /**
     * Devuelve [true, producto|null] o [false, cuerpo del error de Siigo].
     */
    private function buscar_con_paginacion(string $token, string $codigo): array
    {
        for ($pagina = 0; $pagina < self::PAGINAS_BUSQUEDA_PRODUCTO; $pagina++) {
            $response = $this->autocomplete($token, [
                'type'          => 1,
                'browserID'     => '33',
                'query'         => $codigo,
                'filter'        => 'IsInventoryControl=1',
                'numRecordView' => $pagina * 10,
                'tags'          => (object) [],
            ]);

            if (!$response->successful()) {
                return [false, $response->body()];
            }

            $rawBody = trim($response->body());

            if ($rawBody === '""' || $rawBody === '' || $rawBody === 'null') {
                return [true, null];
            }

            $data = json_decode($response->json(), true);

            if (empty($data)) {
                Log::warning('Traslado masivo: autocompletado sin datos legibles', [
                    'codigo' => $codigo,
                    'pagina' => $pagina,
                    'status' => $response->status(),
                    'tipo'   => gettype($response->json()),
                    'body'   => mb_substr($rawBody, 0, 500),
                ]);

                return [true, null];
            }

            $producto = collect($data)->where('Code', $codigo)->first();

            Log::info('Traslado masivo: consulta de producto', [
                'codigo'      => $codigo,
                'pagina'      => $pagina,
                'resultados'  => count($data),
                'codigos'     => collect($data)->pluck('Code')->all(),
                'encontrado'  => (bool) $producto,
            ]);

            if ($producto) {
                return [true, $producto];
            }
        }

        return [true, null];
    }

    private function validar_bodega_salida(string $token, array $detalles, array $config): array
    {
        return $this->validar_existencias($token, $detalles, $config, 'WarehouseCode', 'salida');
    }

    private function validar_bodega_entrada(string $token, array $detalles, array $config): array
    {
        return $this->validar_existencias($token, $detalles, $config, 'DestinationWarehouseCode', 'entrada');
    }

    private function validar_existencias(string $token, array $detalles, array $config, string $campo, string $tipo): array
    {
        $validate = [];
        $fecha = Carbon::parse($config['fecha'])->format('Ymd');
        $esSalida = $campo === 'WarehouseCode';

        foreach ($detalles as $row => $detalle) {
            $productCode = $detalle['ProductCode'];
            $warehouseCode = $detalle[$campo];

            $response = $this->autocomplete($token, [
                'type'          => 1,
                'browserID'     => '67',
                'query'         => '',
                'filter'        => "productcode = {$productCode} AND period < CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'))",
                'filter2'       => "transactiondate >= CONCAT(Year('$fecha'), FORMAT(CONVERT(datetime, '$fecha'), 'MM'), '01') AND CONVERT(DATE, transactiondate, 120) <= CONVERT(DATE, '$fecha', 120) AND productcode = {$productCode}",
                'numRecordView' => 0,
                'tags'          => (object) [
                    '{FilterWareHouse}' => "{condition} ProductWarehouseId = {$warehouseCode}",
                ],
            ]);

            if (!$response->successful()) {
                return [$this->error_general('ERROR DESCONOCIDO', $response->body())];
            }

            $data = $response->json();
            $bodegas = !empty($data) ? json_decode($data, true) : [];

            $existencia = collect($bodegas)->where('WarehouseID', $warehouseCode)->first();

            if (!$existencia) {
                $validate[] = $this->error_detalle($row, $detalle, $warehouseCode, "La bodega de {$tipo} no existe");
                continue;
            }

            if ($esSalida && $config['validar_disponible'] == 'SI' && $existencia['Value'] < $detalle['Quantity']) {
                $validate[] = $this->error_detalle($row, $detalle, $warehouseCode . ' - ' . $existencia['Warehouse'], 'Cantidad insuficiente', [
                    'AvailableQuantity' => $existencia['Value'],
                    'RequiredQuantity'  => $detalle['Quantity'],
                ]);
            }
        }

        return $validate;
    }

    /** En TRANSITO el destino se cambia por la bodega de tránsito de su tienda (stores.siigo_transit_warehouse_id). */
    private function validar_bodega_transito(array $detalles): array
    {
        $validate = [];

        foreach ($detalles as $row => &$detalle) {
            $destino = $detalle['DestinationWarehouseCode'];
            $tienda = $this->tiendas()->firstWhere('siigo_warehouse_id', (int) $destino);

            if (!$tienda) {
                $validate[] = $this->error_detalle($row, $detalle, $destino . ' - #N/A', 'La bodega de entrada no está configurada para traslado');
            } elseif (!$tienda->siigo_transit_warehouse_id) {
                $validate[] = $this->error_detalle($row, $detalle, $destino . ' - ' . $tienda->name, 'La bodega de entrada no tiene bodega de transito configurada');
            } else {
                $detalle['DestinationWarehouseCode'] = (int) $tienda->siigo_transit_warehouse_id;
            }
        }
        unset($detalle);

        return [$detalles, $validate];
    }

    private function separar_traslados(array $detalles): array
    {
        return collect($detalles)
            ->groupBy(fn ($detalle) => $detalle['WarehouseCode'] . '-' . $detalle['DestinationWarehouseCode'])
            ->map(fn ($grupo) => $grupo->values()->all())
            ->values()
            ->all();
    }

    /* -----------------------------------------------------------------
     | Creación del traslado en Siigo
     | ----------------------------------------------------------------- */

    private function traslado(string $token, array $detalles, array $config): array
    {
        $body = $this->payload_traslado(
            $detalles,
            Carbon::parse($config['fecha'])->format('Ymd'),
            trim((string) ($config['observacion'] ?? ''))
        );

        $retry = app(SiigoRetryPolicy::class);

        for ($intento = 1; ; $intento++) {
            // Sin reintento HTTP automatico: reenviar un Save podria crear traslados duplicados.
            $response = app(SiigoClient::class)->services($token)
                ->replaceHeaders(['Accept' => '*/*'])
                ->asJson()
                ->connectTimeout(60)
                ->timeout(300)
                ->post(self::PATH_TRANSFER_SAVE, $body);

            if ($response->successful()) {
                break;
            }

            // Falso duplicado: Siigo lo marca si recibe dos creaciones en el mismo segundo.
            if ($retry->shouldRetryDuplicate($response->body(), $intento)) {
                Log::warning('traslado reintentando por posible duplicidad', ['intento' => $intento]);
                $retry->waitBeforeDuplicateRetry();
                continue;
            }

            $intentosTexto = $retry->attemptsText($intento, 'el error persistió.');

            return [[], [$this->error_general('ERROR DESCONOCIDO - TRASLADO', "{$intentosTexto} Respuesta de Siigo: " . $response->body())]];
        }

        $id = $response->json();
        $document = $this->consultar_documento($token, $id);

        $salida = $detalles[0]['WarehouseCode'];
        $ingreso = $detalles[0]['DestinationWarehouseCode'];

        return [
            [
                'preview'          => SiigoLinks::transferNote($id),
                'preview_download' => SiigoLinks::transferNote($id) . '/true',
                'approved'         => $config['tipo'] == 'TRANSITO'
                    ? $this->generar_excel_aprobar_traslado_transito($document['DocName'], $detalles)
                    : null,
                'pdf'              => $this->generar_pdf_traslado($document['DocName'], $id),
                'data'             => [
                    'id'                  => $id,
                    'document'            => $document['DocName'],
                    'user'                => $document['CreatedByUser'],
                    'created_at'          => $document['CreatedByDate'],
                    'date'                => $document['DocDate'],
                    'observation'         => $body['Entry']->Observations,
                    'bodega_salida'       => $salida,
                    'bodega_ingreso'      => $ingreso,
                    'bodega_salida_data'  => $this->bodega($salida),
                    'bodega_ingreso_data' => $this->bodega($ingreso),
                ],
            ],
            [],
        ];
    }

    private function payload_traslado(array $detalles, string $fecha, string $observacion): array
    {
        return [
            'Items'       => $detalles,
            'AttachFiles' => [],
            'EntryType'   => (object) [
                'ERPDocumentTypeID'          => 23215,
                'Name'                       => 'Nota de traslado entre bodegas',
                'Class'                      => 'NT',
                'Code'                       => 'NT',
                'IsAutomaticEnum'            => true,
                'TemplateName'               => 'TransferVoucher',
                'InternalDescription'        => '',
                'ACAccountCode'              => -1,
                'UseCostCenter'              => false,
                'CostCenterDefault'          => null,
                'CostCenterMandatory'        => false,
                'AttachmentsFSItemsGUID'     => null,
                'AllowDecimals'              => false,
                'IsDiscountValue'            => false,
                'IsDiscountPercentaje'       => false,
                'EmailBody'                  => null,
                'EmailHeader'                => null,
                'EmailCommercialConditions'  => null,
            ],
            'Entry'       => (object) [
                'SalesmanCode'           => null,
                'DocName'                => '',
                'DocDate'                => $fecha,
                'DocNumber'              => null,
                'CostCenterCode'         => null,
                'ForeignMoneyCode'       => null,
                'ExchangeValue'          => null,
                'ExchangePersonalized'   => null,
                'Observations'           => $observacion,
                'AccountCode'            => 0,
                'ContactCode'            => null,
                'Header'                 => null,
                'CommercialConditions'   => null,
                'ACEntryCode'            => -1,
                'ERPDocumentTypeCode'    => 23215,
                'IsAllowDecimals'        => null,
                'AttachmentsFSItemsGUID' => '',
            ],
            'ModelType'   => 23,
        ];
    }

    private function generar_excel_aprobar_traslado_transito(string $document, array $detalles): array
    {
        // El tránsito sale hacia la bodega de la tienda a la que pertenece.
        $bodega_salida = $detalles[0]['DestinationWarehouseCode'];
        $bodega_entrada = $this->tiendas()->firstWhere('siigo_transit_warehouse_id', (int) $bodega_salida)?->siigo_warehouse_id;

        $traslado = [[
            'token'              => '',
            'fecha'              => '',
            'validar_disponible' => 'SI',
            'tipo'               => 'DIRECTO',
            'observacion'        => "APROBACION DE TRASLADO DE TRANSITO: {$document}",
        ]];

        $traslado_detalles = array_map(fn ($detalle) => [
            'codigo'         => $detalle['Code'],
            'descripcion'    => $detalle['LongDescription'],
            'bodega_salida'  => $bodega_salida,
            'bodega_entrada' => $bodega_entrada,
            'cantidad'       => $detalle['Quantity'],
        ], $detalles);

        $filename = "transfer_{$document}_" . now()->format('Y_m_d_His') . '.xlsx';
        $path = self::TMP_PATH . "/{$filename}";

        // Archivo temporal en disco privado: solo se adjunta al correo y luego se borra.
        Excel::store(new MasiveTransferSiigoMultiSheetExport($traslado, $traslado_detalles), $path, self::TMP_DISK);

        return [
            'name'         => $bodega_salida . ' - ' . ($this->bodega($bodega_salida)['name'] ?? '#N/A') . ' a ' . $bodega_entrada . ' - ' . ($this->bodega($bodega_entrada)['name'] ?? '#N/A'),
            // Ya no se publica en storage/public: el Excel viaja solo como adjunto.
            'download_url' => null,
            'disk'         => self::TMP_DISK,
            'path'         => $path,
            'file'         => $filename,
        ];
    }

    /**
     * Genera el mismo PDF de la ruta siigo/search_document/NT/pdf y lo guarda para adjuntarlo en los correos.
     * Si falla no detiene el traslado (ya quedó creado en Siigo): el correo sale sin el PDF.
     */
    private function generar_pdf_traslado(string $document, string|int $id): ?array
    {
        try {
            return app(SiigoTransferNTPdfService::class)->store_transfer_note_pdf($document, $id);
        } catch (\Throwable $e) {
            Log::warning('No se pudo generar el PDF del traslado', [
                'document' => $document,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    /* -----------------------------------------------------------------
     | Consultas a Siigo
     | ----------------------------------------------------------------- */

    private function autocomplete(string $token, array $body)
    {
        // Sin Accept: application/json: con esa cabecera Siigo devuelve la lista codificada dos veces
        // y json_decode() ya no la lee (todos los productos salian como inexistentes).
        return $this->siigo($token)
            ->replaceHeaders(['Accept' => '*/*'])
            ->timeout(120)
            ->post(config('siigo.endpoints.services.autocomplete'), $body);
    }

    private function consultar_documento(string $token, string|int $id): array
    {
        $response = $this->siigo($token)
            ->get(self::PATH_TRANSFER_VIEW, ['id' => $id]);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    private function obtener_datos_usuario(string $token): array
    {
        $response = app(SiigoClient::class)->userSettings($token);

        if (!$response->successful()) {
            return [[], [$this->error_general('TOKEN', 'El token no sirve o expiró, genera uno nuevo')]];
        }

        $data = $response->json();

        return [['id' => $data['userID'], 'name' => $data['userName']], []];
    }

    private function siigo(string $token): PendingRequest
    {
        return app(SiigoClient::class)->services($token)->retry(3, 3000)->asJson();
    }

    /* -----------------------------------------------------------------
     | Utilidades
     | ----------------------------------------------------------------- */

    /** Nombre y correo de una bodega (o de su tránsito) según su tienda; sin correo en la tienda, operaciones. */
    private function bodega($codigo): ?array
    {
        $codigo = (int) $codigo;
        $tienda = $this->tiendas()->firstWhere('siigo_warehouse_id', $codigo);
        $transito = false;

        if (!$tienda) {
            $tienda = $this->tiendas()->firstWhere('siigo_transit_warehouse_id', $codigo);
            $transito = true;
        }

        if (!$tienda) {
            return null;
        }

        return [
            'name'   => $transito ? 'TRANSITO ' . $tienda->name : $tienda->name,
            'emails' => [$tienda->email ?: config('siigo.mail.operations')],
        ];
    }

    private function tiendas(): Collection
    {
        return $this->tiendas ??= Store::whereNotNull('siigo_warehouse_id')
            ->get(['name', 'siigo_warehouse_id', 'siigo_transit_warehouse_id', 'email']);
    }

    private function error_general(string $row, string $error): array
    {
        return ['Row' => $row, 'Error' => $error];
    }

    private function error_detalle(int $row, array $detalle, string $warehouse, string $error, array $extra = []): array
    {
        return array_merge([
            'Row'           => $row + 1,
            'ProductCode'   => $detalle['Description'],
            'Description'   => $detalle['LongDescription'],
            'WarehouseCode' => $warehouse,
        ], $extra, ['Error' => $error]);
    }
}

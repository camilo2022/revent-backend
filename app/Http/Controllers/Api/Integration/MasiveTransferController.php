<?php

namespace App\Http\Controllers\Api\Integration;

use App\Exports\MasiveTransferSiigoMultiSheetExport;
use App\Http\Controllers\Api\Integration\Concerns\RespondsWithFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\MasiveTransfers\FormatRequest;
use App\Http\Requests\Api\Integration\MasiveTransfers\UploadRequest;
use App\Http\Resources\Api\Integration\MasiveTransfers\MasiveTransferQueuedResource;
use App\Jobs\ImportMasiveTransferSiigoJob;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

/** Traslados masivos: formato de Excel con las bodegas de Siigo y carga del archivo que despacha el job. */
class MasiveTransferController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure, RespondsWithFile;

    public function __construct(private SiigoClient $client, private SiigoCatalogService $catalog)
    {
    }

    /** Descarga el formato; la lista de bodegas es la misma que arma Integration (sin tránsitos, con SIN ASIGNAR). */
    public function format(FormatRequest $request)
    {
        try {
            $warehouses = $this->catalog->warehousesWithTransit($this->client->token());

            return $this->downloadExcel(new MasiveTransferSiigoMultiSheetExport([], [], $warehouses), 'formato_traslado');
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    public function upload(UploadRequest $request)
    {
        try {
            $transfer = $request->transfer();
            $email = $request->input('email');

            ImportMasiveTransferSiigoJob::dispatch($transfer, $email);

            return $this->successResponse(
                new MasiveTransferQueuedResource(['email' => $email, 'lines' => $transfer['traslado_detalles']->count()]),
                'Los traslados se están procesando. El resultado llegará al correo indicado.',
                202
            );
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }
}

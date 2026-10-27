<?php

namespace App\Services\Siigo;

use Carbon\Carbon;

/**
 * Documentos de venta por centro de costo (reporte 5430 de Siigo): nombre del cliente,
 * fecha y hora exacta de cada factura y su enlace en Siigo Nube. La API pública no entrega
 * esos datos, por eso la hoja 360 los toma de este reporte.
 */
class SiigoSalesDocumentsService
{
    public function __construct(private SiigoClient $client)
    {
    }

    /**
     * @param array<int|string,array{name:string}> $costCenters centros de costo indexados por id
     * @return array<string,array{url:string,date:string,name:string}> indexado por nombre del documento (ej. FV-1-123)
     */
    public function byCostCenter(string $token, array $costCenters, string $createdStart, string $createdEnd): array
    {
        $dateFrom = Carbon::parse($createdStart)->format('Ymd');
        $dateTo = Carbon::parse($createdEnd)->format('Ymd');

        $documents = [];

        foreach ($costCenters as $costCenterId => $costCenter) {
            $data = $this->fetch($token, (int) $costCenterId, $costCenter['name'], $dateFrom, $dateTo);

            foreach ($data as $item) {
                $documents[$item['DocName']] = [
                    'url' => SiigoLinks::salesInvoice($item['ACEntryID']),
                    'date' => $item['DocDate'],
                    'name' => strtoupper($item['FullName']),
                ];
            }
        }

        return $documents;
    }

    private function fetch(string $token, int $costCenterId, string $costCenterName, string $dateFrom, string $dateTo): array
    {
        $filter = base64_encode(json_encode([
            ['Field' => 'AccountID', 'Value' => []],
            ['Field' => 'DocDate', 'Value' => [$dateFrom, $dateTo, null]],
            ['Field' => '_var_IncudeCreditNote', 'Value' => ['1']],
            ['Field' => 'CostCenterCode', 'Value' => []],
            ['Field' => 'Currency', 'Value' => ['ALL']],
        ], JSON_UNESCAPED_UNICODE));

        $condition = base64_encode(base64_encode(" AND ED.CostCenterCode = {$costCenterId}"));

        $params = json_encode([
            'IncludeRetICAAndRetIVA' => '1',
            'Title' => base64_encode($costCenterName),
            'Filter' => $filter,
            'LngTitle' => '28848',
            'Condition' => $condition,
            'TabID' => '1636',
        ], JSON_UNESCAPED_UNICODE);

        $body = [
            'AddOns' => [config('siigo.report_addons.pos_web')],
            'FilterCriterias' => '[]',
            'GetTotalCount' => false,
            'GridOrderCriteria' => null,
            'Id' => 5430,
            'Params' => $params,
            'Skip' => 0,
            'Sort' => ' ',
            'Take' => 0,
        ];

        $response = $this->client->services($token)
            ->timeout(180)
            ->asJson()
            ->post(config('siigo.endpoints.services.acreport'), $body);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json('data.Value.Table') ?? [];
    }
}

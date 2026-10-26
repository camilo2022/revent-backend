<?php

namespace App\Services\Siigo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * Consultas y creación de órdenes de compra contra el ERP monolito de Siigo
 * (ASPX / ashx). Todas necesitan el token y la cookie de sesión del usuario.
 * Reemplaza las llamadas HTTP repetidas del controlador y del job de carga masiva.
 */
class SiigoPurchaseOrderMonolith
{
    private const AUTOCOMPLETE = '/Framework/Controls/AutoComplete.ashx';
    private const INVOICE_HANDLER = '/Components/ERP/InvoiceHandler.ashx';

    public function __construct(private SiigoClient $client, private SiigoCatalogService $catalog)
    {
    }

    /** Extrae el token de usuario de la cookie pegada por la persona. */
    public function tokenFromCookie(string $cookie): ?string
    {
        preg_match('/TKNSGRDDREVENTCALZADOSAS=([^;]+)/', $cookie, $matches);

        return $matches[1] ?? null;
    }

    /** URL absoluta (solo host) para rutas que Siigo devuelve ya con el nombre de empresa. */
    public function absoluteUrl(string $path): string
    {
        $parts = parse_url(config('siigo.urls.monolith'));

        return $parts['scheme'] . '://' . $parts['host'] . $path;
    }

    /** Configuración del tipo de orden de compra (objeto ERPConfigItem). */
    public function purchaseOrderType(string $token, string $cookie, string|int $purchaseOrderTypeId)
    {
        $response = $this->multipart($token, $cookie)->post($this->docHandler(), [
            ['name' => 'Process', 'contents' => '3'],
            ['name' => 'ERPDocumentTypeID', 'contents' => $purchaseOrderTypeId],
            ['name' => 'ERPDocType', 'contents' => '0'],
            ['name' => 'ERPDocumentID', 'contents' => ''],
        ]);

        return json_decode($response->json('ERPConfigItem'));
    }

    /** Impuestos y retenciones disponibles para una orden de compra. */
    public function listTaxes(string $token, string $cookie): array
    {
        $response = $this->multipart($token, $cookie)->post($this->docHandler(), [
            ['name' => 'Process', 'contents' => '2'],
            ['name' => 'ERPDocType', 'contents' => '0'],
            ['name' => 'IsExpense', 'contents' => '1'],
            ['name' => 'ERPDocumentID', 'contents' => '-1'],
        ]);

        $data = $response->json();

        $impCargo = json_decode($data['lstTaxAdd'], true);
        $impRetencion = json_decode($data['lstTaxDisc'], true);

        $noAplica = ['Id' => -1, 'Name' => 'No aplica', 'Value' => -1, 'Type' => -1, 'SubType' => -1];

        $impCargo = array_merge([$noAplica], array_filter($impCargo, fn ($item) => $item['Id'] != -1));

        $impRetencion = array_merge([$noAplica], array_filter($impRetencion, fn ($item) => $item['Id'] != -1));

        return [
            'imp_cargo' => $impCargo,
            'imp_retencion' => $impRetencion,
            'rete_iva' => json_decode($data['lstTaxRetIVA'], true),
            'rete_ica' => json_decode($data['lstTaxRetICA'], true),
        ];
    }

    /** Centros de costo de la API pública. */
    public function costCenters(): array
    {
        $response = $this->client->api()->get(config('siigo.endpoints.api.cost_centers'));

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    /** Bodegas sin las de tránsito y con "SIN ASIGNAR" de primera. */
    public function warehouses(): array
    {
        $data = collect($this->catalog->warehouses())
            ->reject(fn ($item) => str_starts_with(strtoupper($item['name']), 'TRANSITO'))
            ->values()
            ->all();

        return [['id' => '-1', 'name' => 'SIN ASIGNAR'], ...$data];
    }

    public function searchProducts(string $token, string $cookie, ?string $text)
    {
        $response = $this->multipart($token, $cookie)->post(self::AUTOCOMPLETE, [
            ['name' => 'BrowseCode', 'contents' => '2103'],
            ['name' => 'SearchText', 'contents' => $text],
            ['name' => 'RequestWHERE', 'contents' => '&WHERE=Wkk9%2fo6RnE%2fyPn8MLfXxvwt2DDF53VToM8L2f%2fejoUR1sME0VEzILLHPQE8UvncakFqKBXIApFaXhy%2fpxHdOJw%3d%3d'],
            ['name' => 'txtAutoCompleteID', 'contents' => 'Default_ucControlPane0_ctl00_ERPInvoiceGrid_lkpProduct_txtAutoCompleteLookup'],
            ['name' => 'addRecordExtraParams', 'contents' => ''],
        ]);

        return collect($response->json())->where('Code', $text)->first();
    }

    public function searchProduct(string $token, string $cookie, string|int $productId, string $date)
    {
        $response = $this->multipart($token, $cookie)->post(self::INVOICE_HANDLER, [
            ['name' => 'ActionType', 'contents' => '2'],
            ['name' => 'ProductID', 'contents' => $productId],
            ['name' => 'ERPDocType', 'contents' => '22'],
            ['name' => 'MoneyCode', 'contents' => ''],
            ['name' => 'DocDate', 'contents' => $date],
        ]);

        return $response->json();
    }

    public function searchProviders(string $token, string $cookie, string $text)
    {
        $response = $this->multipart($token, $cookie)->post(self::AUTOCOMPLETE, [
            ['name' => 'BrowseCode', 'contents' => '1003'],
            ['name' => 'SearchText', 'contents' => $text],
            ['name' => 'RequestWHERE', 'contents' => '&WHERE=YSnns%2fo%2fazo1C3Mws6a3nso%2f%2bcyeS%2bwyVovfg9TCPOc%3d'],
            ['name' => 'txtAutoCompleteID', 'contents' => 'Default_ucControlPane0_ctl00_PurchaseOrderHeader_oACAccount_txtAutoCompleteLookup'],
            ['name' => 'addRecordExtraParams', 'contents' => 'TypesAsociates=3'],
        ]);

        return collect($response->json())
            ->reject(fn ($item) => $item['ID'] == -1)
            ->values();
    }

    public function searchProvider(string $token, string $cookie, string|int $accountId)
    {
        $response = $this->multipart($token, $cookie)->post($this->docHandler(), [
            ['name' => 'Process', 'contents' => '4'],
            ['name' => 'AccountID', 'contents' => $accountId],
            ['name' => 'ERPDocType', 'contents' => '6'],
            ['name' => 'MoneyCode', 'contents' => ''],
        ]);

        $data = $response->json();

        if (!empty($data)) {
            $data['ID'] = $accountId;
        }

        return $data;
    }

    /** Crea la orden de compra. Devuelve la respuesta cruda para que el job la interprete. */
    public function createOrder(string $token, string $cookie, array $body): Response
    {
        return $this->client->monolith($token, $cookie)
            ->asMultipart()
            ->post($this->docHandler(), [
                ['name' => 'Process', 'contents' => $body['Process']],
                ['name' => 'ERPDocType', 'contents' => $body['ERPDocType']],
                ['name' => 'ERPDocGeneral', 'contents' => json_encode($body['ERPDocGeneral'])],
                ['name' => 'lstERPDocItem', 'contents' => json_encode($body['lstERPDocItem'])],
                ['name' => 'ERPDocumentTotal', 'contents' => json_encode($body['ERPDocumentTotal'])],
                ['name' => 'ERPDocumentDue', 'contents' => $body['ERPDocumentDue']],
                ['name' => 'ERPDocumentPayment', 'contents' => $body['ERPDocumentPayment']],
                ['name' => 'AdvanceValue', 'contents' => $body['AdvanceValue']],
                ['name' => 'PaymentTotal', 'contents' => $body['PaymentTotal']],
                ['name' => 'ERPDocumentConfigModel', 'contents' => $body['ERPDocumentConfigModel']],
                ['name' => 'JSONERPDocType', 'contents' => json_encode($body['JSONERPDocType'])],
            ]);
    }

    /** Página HTML de una orden de compra ya creada (de ahí se lee el número y el enlace). */
    public function orderPage(string $token, string $cookie, int|string $erpDocumentId): Response
    {
        return $this->client->monolith($token, $cookie)
            ->get(config('siigo.endpoints.monolith.default'), [
                'TabID' => config('siigo.links.purchase_order_tab'),
                'ERPDocumentID' => $erpDocumentId,
            ]);
    }

    private function docHandler(): string
    {
        return config('siigo.endpoints.monolith.erp_doc_handler');
    }

    /** Monolito con los tiempos y reintentos de las consultas (autocompletar, impuestos, tipos). */
    private function multipart(string $token, string $cookie): PendingRequest
    {
        return $this->client->monolith($token, $cookie)
            ->timeout(180)
            ->connectTimeout(60)
            ->retry(3, 5000)
            ->asMultipart();
    }
}

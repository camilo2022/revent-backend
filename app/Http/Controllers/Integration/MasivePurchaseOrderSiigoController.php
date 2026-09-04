<?php

namespace App\Http\Controllers\Integration;

use App\Exports\MasivePurchaseOrderSiigoMultiSheetExport;
use App\Http\Controllers\Controller;
use App\Imports\MasivePurchaseOrderSiigoSheetsImport;
use App\Jobs\ImportMasivePurchaseOrderSiigoJob;
use App\Services\SiigoInventoryService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MasivePurchaseOrderSiigoController extends Controller
{
    public function masive_purchase_order()
    {
        $purchase_order_types = [
            23200 => 'OC-1-Orden de compra principal',
            27279 => 'OC-2-Orden de servicio',
            28029 => 'OC-3-Orden Compra Internet',
            30480 => 'OC-4-O.C. Materia Prima',
        ];

        return view('integration.masive_purchase_order', compact('purchase_order_types'));
    }

    public function masive_purchase_order_format(Request $request)
    {
        $cookie = $request->input('cookie');
        $token = $this->extraer_token($cookie);

        $purchase_order_type_id = $request->input('purchase_order_type_id');

        $type_details = [
            '0' => 'Producto',
            '1' => 'Activo fijo',
            '2' => 'Gasto / Cuenta contable'
        ];

        $purchase_order_type = $this->purchase_order_type($token, $cookie, $purchase_order_type_id);

        $list_taxes = $this->list_taxes($token, $cookie);
        $cost_centers = $purchase_order_type->UseCostCenter ? $this->cost_centers() : [];
        $warehouses = $this->warehouses();

        $filters = [
            'use_cost_center' => $purchase_order_type->UseCostCenter,
            'is_rete_iva' => $purchase_order_type->IsReteIva,
            'is_rete_ica' => $purchase_order_type->IsReteIca
        ];

        return Excel::download(new MasivePurchaseOrderSiigoMultiSheetExport($purchase_order_type, $filters, $warehouses, $cost_centers, $list_taxes, $type_details), "formato_{$purchase_order_type->ERPDocClass}_{$purchase_order_type->ERPDocCode}.xlsx");
    }

    public function masive_purchase_order_upload(Request $request)
    {
        $request->validate([
            'file'  => 'required|file|mimes:xlsx,xls',
            'email' => ['required', 'email'],
            'referencia' => 'required|string',
        ]);

        $referencia = $request->input('referencia');

        $email = $request->input('email');

        $order = Excel::toCollection(new MasivePurchaseOrderSiigoSheetsImport, $request->file('file'));

        ImportMasivePurchaseOrderSiigoJob::dispatch($order, $email, $referencia);

        return view('integration.masive_purchase_order_upload', compact('email'));
    }

    private function purchase_order_type(string $token, string $cookie, string|int $purchase_order_type_id)
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->asMultipart()
            ->post('https://monolithprod.siigo.com/REVENTCALZADOSAS/Components/ERP/Business/ERPDocHandler.ashx', [
                ['name' => 'Process', 'contents' => '3'],
                ['name' => 'ERPDocumentTypeID', 'contents' => $purchase_order_type_id],
                ['name' => 'ERPDocType', 'contents' => '0'],
                ['name' => 'ERPDocumentID', 'contents' => ''],
            ]);

        $data = json_decode($response->json('ERPConfigItem'));
        return $data;
    }

    private function list_taxes(string $token, string $cookie)
    {
        $response = Http::withToken($token)->withHeaders([
                'Cookie' => $cookie,
            ])
            ->timeout(600)
            ->withoutRedirecting()
            ->asMultipart()
            ->post('https://monolithprod.siigo.com/REVENTCALZADOSAS/Components/ERP/Business/ERPDocHandler.ashx', [
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

    private function cost_centers(): array
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $response = Http::retry(5, 10000)->withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id'    => 'consultadeFacturas',
        ])->get("https://api.siigo.com/v1/cost-centers");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        return $data;
    }

    private function warehouses(): array
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $response = Http::retry(5, 10000)->withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id'    => 'consultadeFacturas',
        ])->get("https://api.siigo.com/v1/warehouses");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        $data = collect($data)
            ->reject(fn ($item) => str_starts_with(strtoupper($item['name']), 'TRANSITO'))
            ->values()
            ->all();

        $data = [['id' => '-1', 'name' => 'Sin asignar'], ...$data];
        return $data;
    }

    private function extraer_token(string $cookie): ?string
    {
        preg_match('/TKNSGRDDREVENTCALZADOSAS=([^;]+)/', $cookie, $matches);

        return $matches[1] ?? null;
    }

}

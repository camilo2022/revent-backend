<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SiigoController extends Controller
{
    use ApiMessage, ApiResponser;

    private string $siigo_base_url = 'https://api.siigo.com';
    private string $siigo_username = 'reventgestion@gmail.com';
    private string $siigo_access_key = 'NWIwZTQ3ZmUtZjg0ZS00YzU0LWJlZjYtNzliMGIyOWIxMzk2Oj0/aTw2UDlxWFo=';

    private string $unico_base_url = 'https://qa-api.unicoanalytics.com.co';
    private string $unico_name = 'test_revent';
    private string $unico_password = '7nsw9R8KCsrX';

    public function sync(Request $request)
    {
        try {
            $token_siigo = $this->auth_siigo();
            $invoices = $this->invoices_siigo($token_siigo, [
                'created_start' => $request->input('created_start'),
                'created_end' => $request->input('created_end'),
                'page' => $request->input('page', 1),
                'page_size' => $request->input('page_size', 100)
            ]);

            $processedInvoices = $this->process_invoices($invoices);

            $token_unico = $this->auth_unico();
            $purchases = $this->purchases_unico($token_unico, $processedInvoices);
            return $this->successResponse(
                $purchases,
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    private function auth_siigo(): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Partner-Id'   => 'consultadeFacturas',
        ])->post("{$this->siigo_base_url}/auth", [
            'username'   => $this->siigo_username,
            'access_key' => $this->siigo_access_key,
        ]);

        if (! $response->successful()) {
            throw new \Exception(
                'Error autenticando en Siigo: ' . $response->body()
            );
        }

        return $response->json('access_token');
    }

    private function invoices_siigo(string $token, array $filters = [])
    {
        $allowedFilters = [
            'created_start',
            'created_end',
            'updated_start',
            'updated_end',
            'name',
            'customer_identification',
            'customer_branch_office',
            'document_id',
            'date_start',
            'date_end',
            'page',
            'page_size'
        ];

        $queryParams = array_filter(
            array_intersect_key($filters, array_flip($allowedFilters)),
            fn ($value) => $value !== null && $value !== ''
        );

        $url = "{$this->siigo_base_url}/v1/invoices?" . http_build_query($queryParams);

        $allInvoices = [];

        do {

            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => $token,
                'Partner-Id'    => 'consultadeFacturas',
            ])->get($url);

            if (! $response->successful()) {
                throw new \Exception(
                    'Error consultando facturas: ' . $response->body()
                );
            }

            $data = $response->json();

            // Acumular facturas
            if (!empty($data['results'])) {
                $allInvoices = array_merge($allInvoices, $data['results']);
            }

            // Obtener siguiente página
            $url = $data['_links']['next']['href'] ?? null;

            // Ya no necesitamos query params porque next.href los trae
            $queryParams = [];

        } while ($url);

        return $allInvoices;
    }

    private function process_invoices(array $invoices): array
    {
        return array_values(array_filter(array_map(function ($invoice) {
            foreach($invoice['items'] as $item) {
                [$nombre, $color, $category, $size] = array_pad(explode('*', $item['description']), 4, '#N/A');
                if(isset($item['taxes'])) {
                    return [
                        'document_number' => $invoice['customer']['identification'],
                        'place_local_code' => (string) $invoice['cost_center'],
                        'mall_id' => 1,
                        'purchase_mode_id' => 1,
                        'purchase_date' => Carbon::parse($invoice['date'])->format('d/m/Y'),
                        'purchase_number' => $invoice['name'],
                        'purchase_discount' => null,
                        'purchase_taxes' => collect($item['taxes'])->sum('value'),
                        'purchase_subtotal' => $item['price'],
                        'purchase_total' => $item['total'],
                        'purchase_channel_id' => 1,
                        'name' => $nombre,
                        'category' => $category,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'discount' => null,
                        'code' => $item['code'],
                        'size' => $size,
                        'color' => $color,
                        'description' => $item['description'],
                    ];
                }

                return null;
            }
        }, $invoices)));
    }

    private function auth_unico(): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("{$this->unico_base_url}/api/v1/api-users/auth/login", [
            'name'   => $this->unico_name,
            'password' => $this->unico_password,
        ]);

        if (! $response->successful()) {
            throw new \Exception(
                'Error autenticando en Unico: ' . $response->body()
            );
        }

        return $response->json('data.access_token');
    }

    private function purchases_unico(string $token, array $purchases)
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->post("{$this->unico_base_url}/api/v1/api-users/purchases", [
                'purchases' => $purchases,
            ]);

        if (! $response->successful()) {
            return $response->body();
        }

        return $response->json();
    }
}

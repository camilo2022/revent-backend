<?php

namespace App\Jobs;

use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SyncUnicoSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $siigo_base_url = 'https://api.siigo.com';
    private string $siigo_username = 'reventgestion@gmail.com';
    private string $siigo_access_key = 'NWIwZTQ3ZmUtZjg0ZS00YzU0LWJlZjYtNzliMGIyOWIxMzk2Oj0/aTw2UDlxWFo=';

    private string $unico_base_url = 'https://api.unicoanalytics.com.co';
    private string $unico_name = 'locatario_revent';
    private string $unico_password = 'sV5vNCku8[&0';

    private string $fidelizacion_url = 'https://fidelizacionapi.uniapps.com.co/api/fidelizacion/contact/find-contact-list';

    private array $unico_summary = [
        'enviadas' => 0,
        'insertadas' => 0,
        'rechazadas' => 0,
        'detalle_rechazadas' => [],
    ];

    public function __construct(
        public array $params
    ) {}

    public function handle(): void
    {
        try {
            $token_siigo = $this->auth_siigo();
            $token_unico = $this->auth_unico();

            $invoices = $this->invoices_siigo($token_siigo, $token_unico, $this->params);

            $grouped = collect($invoices)
                ->groupBy('mall_id')
                ->map(function ($items, $mallId) {
                    return [
                        'mall_id' => $mallId,
                        'purchase_subtotal' => $items->sum('purchase_subtotal'),
                        'purchase_total' => $items->sum('purchase_total'),
                        'price' => $items->sum('price'),
                        'invoices' => $items->count(),
                    ];
                })
                ->values();

            $this->notify_result(true, [
                'total_invoices' => count($invoices),
                'grouped' => $grouped,
                'unico_summary' => $this->unico_summary,
            ]);
        } catch (\Throwable $e) {
            $this->notify_result(false, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->notify_result(false, ['error' => $exception->getMessage()]);
    }

    private function notify_result(bool $success, array $details): void
    {
        try {
            Mail::raw(
                $success
                    ? "✅ Sincronización Unico/Siigo completada.\n\nDetalles:\n" . json_encode($details, JSON_PRETTY_PRINT)
                    : "❌ Sincronización Unico/Siigo falló.\n\nDetalles:\n" . json_encode($details, JSON_PRETTY_PRINT),
                function ($message) use ($success) {
                    $message->to(['tecnologia@revent.com.co'])
                        ->subject($success
                            ? 'Sync Unico/Siigo: Completado'
                            : 'Sync Unico/Siigo: Error');
                }
            );
        } catch (\Throwable $mailException) {
            Log::warning('No se pudo enviar notificación de SyncUnicoSiigoJob', [
                'error' => $mailException->getMessage(),
            ]);
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
            throw new Exception('Error autenticando en Siigo: ' . $response->body());
        }

        return $response->json('access_token');
    }

    private function invoices_siigo(string $token_siigo, string $token_unico, array $filters = []): array
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

        $allPurchases = [];

        do {
            $response = Http::retry(5, 10000)->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => $token_siigo,
                'Partner-Id'    => 'consultadeFacturas',
            ])->get($url);

            if ($response->status() === 429) {
                sleep(1);
                continue;
            }

            if (! $response->successful()) {
                throw new Exception('Error consultando facturas: ' . $response->body());
            }

            $data = $response->json();

            if (!empty($data['results'])) {
                $processedInvoices = $this->process_invoices($data['results']);
                $this->purchases_unico($token_unico, $processedInvoices);
                $allPurchases = array_merge($allPurchases, $processedInvoices);
            }

            $url = $data['_links']['next']['href'] ?? null;

            if ($url) usleep(500000);

            unset($data, $response);
        } while ($url);

        return $allPurchases;
    }

    private function process_invoices(array $invoices): array
    {
        $valid_documents = $this->valid_documents($invoices);

        $malls = [
            704 => (object) [
                "fantasy_name" => "REVENT CALZADO",
                "place_local_code" => "162",
                "place_number" => "247",
                "mall_id" => 1,
                "mall_name" => "UNICO CALI"
            ],
            596 => (object) [
                "fantasy_name" => "REVENT CALZADO",
                "place_local_code" => "476",
                "place_number" => "057",
                "mall_id" => 2,
                "mall_name" => "UNICO BARRANQUILLA"
            ]
        ];

        $result = [];

        foreach ($invoices as $invoice) {
            if (!isset($malls[$invoice['cost_center']])) {
                continue;
            }

            foreach ($invoice['items'] ?? [] as $item) {
                $parts = preg_split('/[*-]/', $item['description'] ?? '');
                $count = count($parts);

                $name = $parts[0] ?? '#N/A';
                $color = $parts[1] ?? '#N/A';
                $category = '#N/A';
                $size = '#N/A';

                if ($count === 3) {
                    $size = $parts[2] ?? '#N/A';
                } elseif ($count === 4) {
                    $category = $parts[2] ?? '#N/A';
                    $size = $parts[3] ?? '#N/A';
                } elseif ($count >= 5) {
                    $category = $parts[$count - 2] ?? '#N/A';
                    $size = $parts[$count - 1] ?? '#N/A';
                }

                if ($item['code'] == 'G18022025') continue;

                $result[] = [
                    'document_number' => in_array($invoice['customer']['identification'] ?? null, $valid_documents)
                        ? $invoice['customer']['identification']
                        : '22222222222',
                    'place_local_code' => $malls[$invoice['cost_center']]->place_local_code,
                    'mall_id' => $malls[$invoice['cost_center']]->mall_id,
                    'purchase_mode_id' => 1,
                    'purchase_date' => Carbon::parse($invoice['date'])->format('d/m/Y'),
                    'purchase_number' => $invoice['name'],
                    'purchase_discount' => null,
                    'purchase_taxes' => collect($item['taxes'] ?? [])->sum('value'),
                    'purchase_subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 0),
                    'purchase_total' => $item['total'] ?? 0,
                    'purchase_channel_id' => 1,
                    'name' => $name,
                    'category' => $category,
                    'quantity' => $item['quantity'] ?? null,
                    'price' => $item['price'] ?? null,
                    'discount' => null,
                    'sku' => $item['code'] ?? null,
                    'size' => $size,
                    'color' => $color,
                    'description' => $item['description'] ?? null,
                ];
            }
        }

        return $result;
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
            throw new Exception('Error autenticando en Unico: ' . $response->body());
        }

        return $response->json('data.access_token');
    }

    private function purchases_unico(string $token, array $purchases): array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->post("{$this->unico_base_url}/api/v1/api-users/purchases", [
                'purchases' => $purchases,
            ]);

        if (! $response->successful()) {
            throw new Exception('Error cargando ventas en Unico: ' . $response->body());
        }

        $json = $response->json();

        $data = $json['data'] ?? [];

        $this->unico_summary['enviadas'] += $data['enviadas'] ?? 0;
        $this->unico_summary['insertadas'] += $data['insertadas'] ?? 0;
        $this->unico_summary['rechazadas'] += $data['rechazadas'] ?? 0;
        $this->unico_summary['detalle_rechazadas'] = array_merge(
            $this->unico_summary['detalle_rechazadas'],
            $data['detalle_rechazadas'] ?? []
        );

        return $json;
    }

    private function valid_documents(array $invoices): array
    {
        $documents = collect($invoices)
            ->pluck('customer.identification')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($documents)) {
            return [];
        }

        $response = Http::acceptJson()->post($this->fidelizacion_url, [
            'cedulas' => $documents,
        ]);

        if (!$response->successful()) {
            return [];
        }

        return $response->json('data', []);
    }
}

<?php

namespace App\Jobs;

use App\Exports\PurchaseSiigoExport;
use App\Services\SiigoInventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class ExportPurchaseSiigoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    private string $siigo_base_url = 'https://api.siigo.com';
    private array $cost_centers = [];
    private array $purchases = [];

    public function __construct(
        public array $filters,
        public array|string $notifyEmail
    ) {}

    public function handle(): void
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $this->cost_centers_siigo($token);
        $this->purchases_siigo($token, $this->filters);

        $products = $siigo->getProducts($token, $this->filters);

        $filename = 'compras_' . now()->format('Y_m_d_His') . '.xlsx';

        Excel::store(
            new PurchaseSiigoExport(
                $token,
                $this->cost_centers,
                $this->purchases,
                $products,
                $this->stores(),
                $this->siigo_base_url
            ),
            "exports/{$filename}",
            'public'
        );

        $downloadUrl = route('exports.download', ['file' => $filename]);

        Mail::raw(
            "✅ Tu exportación de compras Siigo está lista.\n\nDescarga: {$downloadUrl}",
            fn ($msg) => $msg
                ->to($this->notifyEmail)
                ->subject("Exportación de compras lista: {$filename}")
        );
    }

    public function failed(\Throwable $e): void
    {
        Mail::raw(
            "❌ Falló la exportación de compras Siigo.\n\nError: {$e->getMessage()}",
            fn ($msg) => $msg
                ->to($this->notifyEmail)
                ->subject("Error en exportación de compras Siigo")
        );
    }

    private function cost_centers_siigo(string $token): void
    {
        $response = Http::retry(5, 10000)->withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id'    => 'consultadeFacturas',
        ])->get("{$this->siigo_base_url}/v1/cost-centers");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        $this->cost_centers = collect($data ?? [])->keyBy('id')->all();
    }

    private function purchases_siigo(string $token, array $filters = []): void
    {
        $allowedFilters = ['page', 'page_size', 'created_start', 'created_end'];

        $queryParams = array_filter(
            array_intersect_key($filters, array_flip($allowedFilters)),
            fn ($value) => $value !== null && $value !== ''
        );

        $createdStart = isset($filters['created_start']) ? new \DateTime($filters['created_start']) : null;
        $createdEnd   = isset($filters['created_end'])   ? new \DateTime($filters['created_end'])   : null;

        $url = "{$this->siigo_base_url}/v1/purchases?" . http_build_query($queryParams);

        do {
            $response = Http::retry(5, 10000)->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => $token,
                'Partner-Id'    => 'consultadeFacturas',
            ])->get($url);

            if ($response->status() === 429) {
                sleep(1);
                continue;
            }

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            $data = $response->json();

            if (!empty($data['results'])) {
                foreach ($data['results'] as $item) {
                    $itemDate = new \DateTime($item['date']);

                    $withinRange = (!$createdStart || $itemDate >= $createdStart)
                        && (!$createdEnd || $itemDate <= $createdEnd);

                    if ($withinRange) {
                        $this->purchases[] = $item;
                    }
                }
            }

            $url = $data['_links']['next']['href'] ?? null;

            if ($url) {
                usleep(500000);
            }

            unset($data, $response);
        } while ($url);
    }

    private function stores(): array
    {
        return [
            3 => ['name' => 'ALEGRA', 'code' => 'G2'],
            4 => ['name' => 'PUNTO DE VENTA', 'code' => 'P7'],
            9 => ['name' => 'MAYALES', 'code' => 'M3'],
            15 => ['name' => 'REVENT', 'code' => 'R1'],
            17 => ['name' => 'OCEAN MALL', 'code' => 'P2'],
            19 => ['name' => 'NUESTRO', 'code' => 'P1'],
            22 => ['name' => 'ALAMEDAS', 'code' => 'P3'],
            24 => ['name' => 'PORTAL', 'code' => 'M2'],
            28 => ['name' => 'PLAZA DEL SOL', 'code' => 'M8'],
            31 => ['name' => 'WEB', 'code' => 'G1'],
            32 => ['name' => 'CASTELLANA', 'code' => 'G4'],
            33 => ['name' => 'UNICO BQ', 'code' => 'G3'],
            37 => ['name' => 'CARNAVAL', 'code' => 'P4'],
            46 => ['name' => 'GUATAPURI', 'code' => 'M7'],
            49 => ['name' => 'VENTURA PLAZA', 'code' => 'M6'],
            50 => ['name' => 'FABRICATO', 'code' => 'M5'],
            51 => ['name' => 'UNICO CALI', 'code' => 'M1'],
            56 => ['name' => 'CARIBE PLAZA 2', 'code' => 'G5'],
            57 => ['name' => 'MAYORCA', 'code' => 'M4'],
            58 => ['name' => 'GRAN MANZANA', 'code' => 'I3'],
            59 => ['name' => 'NUESTRO ATLANTICO', 'code' => 'P6'],
        ];
    }
}

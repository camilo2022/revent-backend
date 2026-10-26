<?php

namespace App\Http\Controllers\Integration;

use App\Exports\MasiveTransferSiigoMultiSheetExport;
use App\Http\Controllers\Controller;
use App\Imports\MasiveTransferSiigoSheetsImport;
use App\Jobs\ImportMasiveTransferSiigoJob;
use App\Services\Siigo\SiigoClient;
use App\Http\Requests\Integration\MasiveTransfer\MasiveTransferUploadRequest;
use Maatwebsite\Excel\Facades\Excel;

class MasiveTransferSiigoController extends Controller
{
    public function __construct(private SiigoClient $siigo)
    {
    }

    public function masive_transfer()
    {
        return view('integration.masive_transfer.index');
    }

    public function masive_transfer_format()
    {
        $warehouses = $this->warehouses();

        return Excel::download(new MasiveTransferSiigoMultiSheetExport([], [], $warehouses), "formato_traslado.xlsx");
    }

    public function masive_transfer_upload(MasiveTransferUploadRequest $request)
    {
        $email = $request->input('email');

        $transfer = Excel::toCollection(new MasiveTransferSiigoSheetsImport, $request->file('file'));

        ImportMasiveTransferSiigoJob::dispatch($transfer, $email);

        return view('integration.masive_transfer.upload', compact('email'));
    }

    private function warehouses()
    {
        $response = $this->siigo->api()->get(config('siigo.endpoints.api.warehouses'));

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        return $this->processWarehouses($data);
    }

    private function processWarehouses(array $data): array
    {
        $items = collect($data)->map(function ($item) {
            return (array) $item;
        });

        $nombresConTransito = $items
            ->filter(fn ($item) => str_starts_with(trim($item['name']), 'TRANSITO '))
            ->map(fn ($item) => trim(str_replace('TRANSITO ', '', $item['name'])))
            ->map(fn ($nombre) => mb_strtoupper(trim($nombre)))
            ->unique()
            ->values();

        $result = $items
            ->filter(fn ($item) => ! str_starts_with(trim($item['name']), 'TRANSITO '))
            ->map(function ($item) use ($nombresConTransito) {
                $nombreNormalizado = mb_strtoupper(trim($item['name']));

                $item['transito'] = $nombresConTransito->contains($nombreNormalizado);

                return $item;
            })
            ->values()
            ->toArray();

        array_unshift($result, [
            'id' => -1,
            'name' => 'SIN ASIGNAR',
            'transito' => false,
        ]);

        return $result;
    }
}

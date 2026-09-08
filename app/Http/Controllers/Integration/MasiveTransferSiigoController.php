<?php

namespace App\Http\Controllers\Integration;

use App\Exports\MasiveTransferSiigoMultiSheetExport;
use App\Http\Controllers\Controller;
use App\Imports\MasiveTransferSiigoSheetsImport;
use App\Jobs\ImportMasiveTransferSiigoJob;
use App\Services\SiigoInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;

class MasiveTransferSiigoController extends Controller
{
    private string $siigo_base_url = 'https://api.siigo.com';

    public function masive_transfer()
    {
        return view('integration.masive_transfer');
    }

    public function masive_transfer_format()
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();
        $warehouses = $this->warehouses($token);

        return Excel::download(new MasiveTransferSiigoMultiSheetExport([], [], $warehouses), "formato_traslado.xlsx");
    }

    public function masive_transfer_upload(Request $request)
    {
        $request->validate([
            'file'  => 'required|file|mimes:xlsx,xls',
            'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@revent\.com\.co$/'],
        ], [
            'email.regex' => 'El correo :input debe pertenecer al dominio @revent.com.co',
        ]);

        $email = $request->input('email');

        $transfer = Excel::toCollection(new MasiveTransferSiigoSheetsImport, $request->file('file'));

        ImportMasiveTransferSiigoJob::dispatch($transfer, $email);

        return view('integration.masive_transfer_upload', compact('email'));
    }

    private function warehouses(string $token)
    {
        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id' => 'consultadeFacturas',
        ])->get("{$this->siigo_base_url}/v1/warehouses");

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

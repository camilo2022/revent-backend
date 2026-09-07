<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\SiigoInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AccountPayableSiigoController extends Controller
{
    public function account_payable()
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $providers = $this->accounts_payable_providers($token);
        $documents = $this->accounts_payable_documents($token);

        $documentsByAccount = collect($documents)->groupBy('AccountID');

        $providers = collect($providers)
            ->map(function ($provider) use ($documentsByAccount) {
                $provider['Documents'] = $documentsByAccount
                    ->get($provider['AccountID'], collect())
                    ->values()
                    ->all();
                return $provider;
            })->all();

        return view('integration.account_payable', compact('providers'));
    }

    private function accounts_payable_providers(string $token)
    {
        $filterCriterias = [
            [
                'Field' => '_AccountID',
                'FilterType' => 6,
                'OperatorType' => 0,
                'Value' => [],
                'ValueUI' => '',
                'Source' => '1',
            ],
            [
                'Field' => 'Currency',
                'FilterType' => 65,
                'OperatorType' => 0,
                'Value' => ['ALL'],
                'ValueUI' => 'Moneda Local',
                'Source' => null,
            ],
        ];

        $body = [
            'Id' => 5438,
            'Skip' => 0,
            'Take' => 0,
            'Sort' => ' ',
            'FilterCriterias' => json_encode($filterCriterias),
            'Params' => json_encode([
                'TabID' => '1630',
                'DUETYPE' => '-1',
                'pTabID' => '1630',
                'rReport' => '1',
            ]),
            'GetTotalCount' => false,
            'GridOrderCriteria' => null,
            'AddOns' => [],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(600)
            ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando cuentas por pagar a proveedores: ' . $response->body()
            );
        }

        return $response->json('data.Value.Table');
    }

    private function accounts_payable_documents(string $token)
    {
        $take = 100;
        $skip = 0;
        $total = null;
        $rows = [];

        do {
            $filterCriterias = [
                [
                    'Field' => '_vClientProv',
                    'FilterType' => 68,
                    'OperatorType' => 0,
                    'Value' => [],
                    'ValueUI' => '',
                    'Source' => 'Account',
                ],
                [
                    'Field' => '_vDueAgeEnum',
                    'FilterType' => 7,
                    'OperatorType' => 0,
                    'Value' => [-1],
                    'ValueUI' => '',
                    'Source' => 'DueAgeEnum',
                ],
                [
                    'Field' => 'Currency',
                    'FilterType' => 65,
                    'OperatorType' => 0,
                    'Value' => ['ALL'],
                    'ValueUI' => 'Moneda Local',
                    'Source' => null,
                ],
            ];

            $body = [
                'Id' => 5436,
                'Skip' => $skip,
                'Take' => $take,
                'Sort' => ' ',
                'FilterCriterias' => json_encode($filterCriterias),
                'Params' => json_encode([
                    'TabID' => '1609',
                    'DueType' => '-1',
                    'pTabID' => '538',
                    'rReport' => '1',
                ]),
                'GetTotalCount' => $total === null,
                'GridOrderCriteria' => null,
                'AddOns' => null,
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(600)
                ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

            if (!$response->successful()) {
                throw new \Exception(
                    'Error consultando cuentas por pagar a proveedores: ' . $response->body()
                );
            }

            if ($total == null) {
                $total = (int) $response->json('totalCount');
            }

            $page = $response->json('data.Value.Table') ?? [];
            $rows = array_merge($rows, $page);

            $skip += $take;
        } while ($skip < $total);

        return $rows;
    }
}

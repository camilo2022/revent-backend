<?php

namespace App\Services\Siigo;

/** Consultas de documentos sueltos: búsqueda rápida y detalle de compras. */
class SiigoDocumentService
{
    public function __construct(private SiigoClient $client)
    {
    }

    /**
     * Búsqueda rápida de Siigo. Devuelve la lista de resultados, o null si Siigo respondió con error.
     */
    public function quicksearch(string $siigoToken, string $query, int $queryType): ?array
    {
        $response = $this->client->services($siigoToken)
            ->asJson()
            ->post(config('siigo.endpoints.services.quicksearch'), [
                'quicksearch' => [
                    'queryString' => $query,
                    'queryType' => $queryType,
                    'typeQuickSearch' => 0,
                ],
            ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json('result.data', $response->json('data', []));
    }

    /** Detalle de una orden/factura de compra (Purchase/GetItem). */
    public function purchaseItem(string $siigoToken, int $acEntryId, string $errorLabel): mixed
    {
        $response = $this->client->services($siigoToken)
            ->get(config('siigo.endpoints.services.purchase_item'), ['id' => $acEntryId]);

        if (!$response->successful()) {
            throw new \Exception("Error consultando detalle {$errorLabel}: " . $response->body());
        }

        return $response->json();
    }
}

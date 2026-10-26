<?php

namespace App\Services\Siigo;

/**
 * Datos del usuario dueño de un token de Siigo (el que la persona pega en pantalla).
 * Reemplaza las copias de obtener_datos_usuario / validar_usuario de los controladores.
 */
class SiigoUserService
{
    public function __construct(private SiigoClient $client)
    {
    }

    /**
     * @return array{success: bool, data: array, message: string}
     *         data = ['id' => int, 'user' => string, 'name' => string] cuando success es true.
     */
    public function fromToken(string $userToken): array
    {
        $response = $this->client->userSettings($userToken);

        if (!$response->successful()) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'Error de autenticación',
            ];
        }

        $data = $response->json();

        if (!isset($data['userID'], $data['userName'], $data['UserOptions']['userPrincipalName'])) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'La respuesta de Siigo no contiene los datos esperados del usuario.',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'id' => (int) $data['userID'],
                'user' => $data['userName'],
                'name' => $data['UserOptions']['userPrincipalName'],
            ],
            'message' => 'Usuario encontrado exitosamente',
        ];
    }
}

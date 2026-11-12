<?php

namespace App\Http\Resources\Api\Integration\Credentials;

use Illuminate\Http\Resources\Json\JsonResource;

/** Resultado de probar el token y/o la cookie de Siigo; nunca repite los valores recibidos. */
class CredentialsCheckResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'credentials' => collect($this->resource)->map(fn ($check) => [
                'valid' => $check['valid'],
                'message' => $check['message'],
                'user' => $check['user'],
            ])->all(),
        ];
    }
}

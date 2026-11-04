<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Respuesta de error para los controladores: las reglas de negocio (DomainException) se muestran tal cual;
 * cualquier otro error se registra en el log y no filtra detalles internos (SQL, tablas, rutas).
 */
trait ApiFailure
{
    protected function failure(\Throwable $e)
    {
        $business = $e instanceof \DomainException;

        if (!$business) {
            Log::error($e->getMessage(), ['exception' => $e]);
        }

        return $this->errorResponse(
            [
                'message' => $business ? $e->getMessage() : $this->getMessage(class_basename($e)),
                'error' => $business || config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado.',
            ],
            $business ? 422 : $this->getCode(class_basename($e))
        );
    }
}

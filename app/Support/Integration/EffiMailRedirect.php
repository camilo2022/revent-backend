<?php

namespace App\Support\Integration;

use Illuminate\Support\Facades\Cache;

/**
 * Redirige los correos de la sincronización con Effi a otro destinatario (para pruebas), sin cambiar a quién deberían ir.
 * Se activa con EFFI_MAIL_TO en el .env o, en caliente y sin reiniciar el worker, con la llave de caché «effi_mail_to».
 * Sin ninguna de las dos, los correos van a quien corresponda (el correo de la bodega WEB).
 */
class EffiMailRedirect
{
    public const CACHE_KEY = 'effi_mail_to';

    /** Destinatario de prueba, o null si los correos deben ir a su destino normal. */
    public static function target(): ?string
    {
        $target = config('effi.mail_to') ?: Cache::get(self::CACHE_KEY);

        return is_string($target) && filter_var($target, FILTER_VALIDATE_EMAIL) ? $target : null;
    }
}

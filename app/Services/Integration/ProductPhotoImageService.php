<?php

namespace App\Services\Integration;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

/**
 * Conversión de fotos de producto y normalización de la referencia.
 * Copiado de Integration\PhotoProductSiigoController::convertir_webp, convertir_jpg y sanitize_referencia.
 */
class ProductPhotoImageService
{
    /** WebP de hasta 1000 px por lado (sin ampliar las pequeñas), calidad 80. */
    public function toWebp(string $contents): string
    {
        $image = (new ImageManager(new Driver()))->decodeBinary($contents);

        $image->scaleDown(width: 1000, height: 1000);

        return $image->encodeUsingFormat(Format::WEBP, quality: 80)->toString();
    }

    /** JPG calidad 90, que es lo que recibe Siigo. */
    public function toJpg(string $contents): string
    {
        return (new ImageManager(new Driver()))->decodeBinary($contents)
            ->encodeUsingFormat(Format::JPEG, quality: 90)
            ->toString();
    }

    /** Referencia como nombre de carpeta: MAYÚSCULAS (con Ñ), números, guion y guion bajo; lo demás pasa a guion. */
    public function sanitizeReference(string $reference): string
    {
        $reference = trim($reference);

        // Unifica la Ñ descompuesta (N + acento combinado, común en Mac) en un solo carácter.
        if (class_exists(\Normalizer::class)) {
            $reference = \Normalizer::normalize($reference, \Normalizer::FORM_C) ?: $reference;
        }

        // Con el modificador /u la Ñ cuenta como un carácter. Devuelve null si el texto no es UTF-8 válido.
        $clean = preg_replace('/[^A-Za-zÑñ0-9\-_]/u', '-', $reference);

        return mb_strtoupper($clean ?? '');
    }

    /** Nombre de referencia de una carga masiva: solo MAYÚSCULAS (con Ñ), números, guion o guion bajo, sin transformar. */
    public function isValidBulkReference(string $reference): bool
    {
        return preg_match('/^[A-ZÑ0-9_-]+$/u', $reference) === 1;
    }
}

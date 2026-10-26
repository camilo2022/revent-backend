<?php

namespace App\Services\Siigo;

/**
 * Pausas y reintentos al crear documentos en Siigo (órdenes de compra, traslados).
 *
 * POR QUÉ EXISTE: Siigo rechaza con "duplicidad" dos peticiones de creación que
 * llegan en el mismo segundo, aunque no sean realmente el mismo documento
 * (falso duplicado). Para evitarlo se espera entre un documento y el siguiente,
 * y si Siigo igual responde duplicidad se espera y se reintenta.
 * Los tiempos y el número de intentos viven en config('siigo.retry') y no deben acortarse.
 */
class SiigoRetryPolicy
{
    /** Pausa obligatoria entre la creación de un documento y la del siguiente. */
    public function pauseBetweenDocuments(): void
    {
        sleep($this->documentPauseSeconds());
    }

    public function documentPauseSeconds(): int
    {
        return (int) config('siigo.retry.document_pause_seconds');
    }

    public function duplicateWaitSeconds(): int
    {
        return (int) config('siigo.retry.duplicate_wait_seconds');
    }

    /** ¿Se debe reintentar? Solo si Siigo dice "duplicidad" y aún quedan intentos. */
    public function shouldRetryDuplicate(?string $message, int $attempt): bool
    {
        return $message !== null
            && str_contains($message, 'duplicidad')
            && $attempt < (int) config('siigo.retry.duplicate_max_attempts');
    }

    /** Espera antes de reintentar un falso duplicado. */
    public function waitBeforeDuplicateRetry(): void
    {
        sleep($this->duplicateWaitSeconds());
    }

    /** Texto para el correo: cuántas veces se intentó. */
    public function attemptsText(int $attempt, string $persisted): string
    {
        return $attempt > 1
            ? "Se intentó {$attempt} veces (con espera de {$this->duplicateWaitSeconds()} segundos entre cada intento) y {$persisted}"
            : 'Se intentó 1 vez.';
    }

    public function consultMaxAttempts(): int
    {
        return (int) config('siigo.retry.consult_max_attempts');
    }

    /** Espera creciente al leer la orden recién creada: paso * número de intento. */
    public function consultWaitSeconds(int $attempt): int
    {
        return (int) config('siigo.retry.consult_wait_step_seconds') * $attempt;
    }
}

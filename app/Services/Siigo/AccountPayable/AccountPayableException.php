<?php

namespace App\Services\Siigo\AccountPayable;

/** Error de negocio de cuentas por pagar que se responde como JSON con un código HTTP concreto. */
class AccountPayableException extends \RuntimeException
{
    public function __construct(string $message, private int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}

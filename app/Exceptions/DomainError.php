<?php

namespace App\Exceptions;

/**
 * Error de dominio/regla de negocio (422)
 */
class DomainError extends AppError
{
    public function __construct(string $message, string $code, ?array $details = null, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, 422, $details, $previous);
    }
}

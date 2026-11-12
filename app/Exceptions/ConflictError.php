<?php

namespace App\Exceptions;

/**
 * Conflicto de negocio (409)
 */
class ConflictError extends AppError
{
    public function __construct(string $message, ?array $details = null, ?\Exception $previous = null)
    {
        parent::__construct($message, 'CONFLICT', 409, $details, $previous);
    }
}

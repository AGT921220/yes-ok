<?php

namespace App\Exceptions;

/**
 * Error de validación (400)
 */
class ValidationError extends AppError
{
    public function __construct(string $message, ?array $details = null, ?\Exception $previous = null)
    {
        parent::__construct($message, 'VALIDATION_ERROR', 400, $details, $previous);
    }
}

<?php

namespace App\Exceptions;

/**
 * Recurso no encontrado (404)
 */
class NotFoundError extends AppError
{
    public function __construct(string $message, ?array $details = null, ?\Exception $previous = null)
    {
        parent::__construct($message, 'NOT_FOUND', 404, $details, $previous);
    }
}

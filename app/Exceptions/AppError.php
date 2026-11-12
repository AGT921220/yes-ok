<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción base para errores de aplicación con códigos específicos
 */
class AppError extends Exception
{
    protected string $errorCode;
    protected int $httpStatus;
    protected ?array $details;

    public function __construct(
        string $message,
        string $errorCode,
        int $httpStatus = 400,
        ?array $details = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->details = $details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * Convertir a formato de respuesta JSON estándar
     */
    public function toArray(): array
    {
        $response = [
            'ok' => false,
            // Mensaje principal a nivel raíz para que el frontend pueda usar data.message
            'message' => $this->getMessage(),
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
            ],
        ];

        if ($this->details) {
            // Detalles estructurados dentro de error
            $response['error']['details'] = $this->details;
            // Y también como errors a nivel raíz para compatibilidad con Laravel estándar
            $response['errors'] = $this->details;
        }

        return $response;
    }
}

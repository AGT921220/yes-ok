<?php

namespace App\Features\WhatsApp\Infrastructure\Meta;

use Exception;

/**
 * Excepción específica para errores de Meta WhatsApp Cloud API
 */
class MetaWhatsAppCloudException extends Exception
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly ?array $responseBody = null,
        private readonly ?string $requestId = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Crea excepción desde respuesta HTTP de Meta
     */
    public static function fromHttpResponse(int $statusCode, array $responseBody, ?string $requestId = null): self
    {
        $errorMessage = $responseBody['error']['message'] ?? 'Error desconocido de WhatsApp Cloud API';
        $errorType = $responseBody['error']['type'] ?? 'Unknown';
        $errorCode = $responseBody['error']['code'] ?? null;
        
        $message = sprintf(
            'WhatsApp Cloud API Error [%d]: %s (Type: %s%s)',
            $statusCode,
            $errorMessage,
            $errorType,
            $errorCode ? ", Code: {$errorCode}" : ''
        );

        return new self($message, $statusCode, $responseBody, $requestId);
    }
}

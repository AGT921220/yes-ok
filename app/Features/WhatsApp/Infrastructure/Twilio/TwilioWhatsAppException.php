<?php

namespace App\Features\WhatsApp\Infrastructure\Twilio;

use Exception;

/**
 * Excepción específica para errores de Twilio WhatsApp API
 */
class TwilioWhatsAppException extends Exception
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly ?array $responseBody = null,
        private readonly ?string $messageSid = null,
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

    public function getMessageSid(): ?string
    {
        return $this->messageSid;
    }

    /**
     * Crea excepción desde respuesta HTTP de Twilio
     */
    public static function fromHttpResponse(int $statusCode, array $responseBody, ?string $messageSid = null): self
    {
        $errorMessage = $responseBody['message'] ?? 'Error desconocido de Twilio';
        $errorCode = $responseBody['code'] ?? null;
        $moreInfo = $responseBody['more_info'] ?? null;
        
        $message = sprintf(
            'Twilio WhatsApp API Error [%d]: %s%s%s',
            $statusCode,
            $errorMessage,
            $errorCode ? " (Code: {$errorCode})" : '',
            $moreInfo ? " - More info: {$moreInfo}" : ''
        );

        return new self($message, $statusCode, $responseBody, $messageSid);
    }
}

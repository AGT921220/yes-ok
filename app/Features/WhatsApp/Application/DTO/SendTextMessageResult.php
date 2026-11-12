<?php

namespace App\Features\WhatsApp\Application\DTO;

/**
 * DTO de resultado del envío de mensaje
 */
final class SendTextMessageResult
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $to,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * Factory method para crear desde respuesta de API
     */
    public static function fromApiResponse(array $response, string $to): self
    {
        $messageId = $response['messages'][0]['id'] ?? 'unknown';
        
        return new self(
            messageId: $messageId,
            to: $to,
            metadata: $response
        );
    }
}

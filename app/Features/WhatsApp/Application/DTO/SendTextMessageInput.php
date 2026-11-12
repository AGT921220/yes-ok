<?php

namespace App\Features\WhatsApp\Application\DTO;

use App\Features\WhatsApp\Domain\ValueObjects\WhatsAppPhone;

/**
 * DTO de entrada para envío de mensaje de texto
 */
final class SendTextMessageInput
{
    public function __construct(
        public readonly string $to,
        public readonly string $message,
    ) {
        if (empty(trim($message))) {
            throw new \InvalidArgumentException('El mensaje no puede estar vacío');
        }
    }

    /**
     * Factory method para crear desde strings
     */
    public static function create(string $to, string $message): self
    {
        return new self(
            $to,
            $message
        );
    }
}

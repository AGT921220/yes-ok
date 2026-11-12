<?php

namespace App\Features\WhatsApp\Infrastructure\Twilio;

use App\Features\WhatsApp\Application\DTO\SendTextMessageInput;
use App\Features\WhatsApp\Application\DTO\SendTextMessageResult;
use App\Features\WhatsApp\Domain\Ports\WhatsAppSender;

/**
 * Adapter (Infrastructure) que implementa el puerto WhatsAppSender
 * Usa Twilio WhatsApp API como proveedor
 */
final class TwilioWhatsAppSender implements WhatsAppSender
{
    public function __construct(
        private readonly TwilioWhatsAppClient $client
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function sendTextMessage(SendTextMessageInput $input): SendTextMessageResult
    {
        // Twilio requiere formato E.164 con prefijo whatsapp:
        $to = $input->to->getE164(); // +521...
        $response = $this->client->sendTextMessage($to, $input->message);

        // Twilio retorna 'sid' como identificador del mensaje
        $messageId = $response['sid'] ?? 'unknown';
        
        return new SendTextMessageResult(
            messageId: $messageId,
            to: $to,
            metadata: $response
        );
    }
}

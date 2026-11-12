<?php

namespace App\Features\WhatsApp\Infrastructure\Meta;

use App\Features\WhatsApp\Application\DTO\SendTextMessageInput;
use App\Features\WhatsApp\Application\DTO\SendTextMessageResult;
use App\Features\WhatsApp\Domain\Ports\WhatsAppSender;

/**
 * Adapter (Infrastructure) que implementa el puerto WhatsAppSender
 * Usa Meta WhatsApp Cloud API como proveedor
 */
final class MetaWhatsAppCloudSender implements WhatsAppSender
{
    public function __construct(
        private readonly MetaWhatsAppCloudClient $client
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function sendTextMessage(SendTextMessageInput $input): SendTextMessageResult
    {
        $to = $input->to->getValue(); // Solo dígitos, sin +
        $response = $this->client->sendTextMessage($to, $input->message);

        return SendTextMessageResult::fromApiResponse($response, $to);
    }
}

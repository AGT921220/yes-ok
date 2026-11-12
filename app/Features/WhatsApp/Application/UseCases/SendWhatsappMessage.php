<?php

namespace App\Features\WhatsApp\Application\UseCases;

use App\Features\WhatsApp\Application\DTO\SendTextMessageInput;
use App\Features\WhatsApp\Application\DTO\SendTextMessageResult;
use App\Features\WhatsApp\Domain\Ports\WhatsAppSender;

/**
 * Caso de uso para envío de mensaje de texto
 * Orquesta la lógica de negocio y delega al puerto
 */
final class SendWhatsappMessage
{
    public function __construct(
        private readonly WhatsAppSender $whatsAppSender
    ) {
    }

    /**
     * Ejecuta el caso de uso
     */
    public function __invoke(SendTextMessageInput $input): SendTextMessageResult
    {
        return $this->whatsAppSender->sendTextMessage($input);
    }
}

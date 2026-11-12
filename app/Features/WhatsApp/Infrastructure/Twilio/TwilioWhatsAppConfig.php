<?php

namespace App\Features\WhatsApp\Infrastructure\Twilio;

use InvalidArgumentException;

/**
 * Configuración para Twilio WhatsApp API
 * Lee y valida variables de entorno
 */
final class TwilioWhatsAppConfig
{
    public function __construct(
        public readonly string $accountSid,
        public readonly string $authToken,
        public readonly string $whatsappFrom,
        public readonly ?string $statusCallbackUrl = null,
    ) {
        $this->validate();
    }

    /**
     * Crea configuración desde variables de entorno
     */
    public static function fromEnv(): self
    {
        $accountSid = config('services.twilio.account_sid');
        dd($accountSid);
        $authToken = config('services.twilio.auth_token');
        $whatsappFrom = config('services.twilio.whatsapp_from', 'whatsapp:+14155238886');
        $statusCallbackUrl = config('services.twilio.status_callback_url');

        return new self(
            accountSid: $accountSid ?? '',
            authToken: $authToken ?? '',
            whatsappFrom: $whatsappFrom,
            statusCallbackUrl: $statusCallbackUrl
        );
    }

    /**
     * Valida que las credenciales requeridas estén presentes
     */
    private function validate(): void
    {
        if (empty($this->accountSid)) {
            throw new InvalidArgumentException(
                'TWILIO_ACCOUNT_SID no está configurado. Agrega esta variable a tu archivo .env'
            );
        }

        if (empty($this->authToken)) {
            throw new InvalidArgumentException(
                'TWILIO_AUTH_TOKEN no está configurado. Agrega esta variable a tu archivo .env'
            );
        }

        if (empty($this->whatsappFrom)) {
            throw new InvalidArgumentException(
                'TWILIO_WHATSAPP_FROM no está configurado. Agrega esta variable a tu archivo .env'
            );
        }
    }

    /**
     * Retorna la URL base de la API de Twilio
     */
    public function getBaseUrl(): string
    {
        return "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}";
    }

    /**
     * Retorna la URL del endpoint de mensajes
     */
    public function getMessagesEndpoint(): string
    {
        return "{$this->getBaseUrl()}/Messages.json";
    }
}

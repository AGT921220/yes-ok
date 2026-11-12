<?php

namespace App\Features\WhatsApp\Infrastructure\Meta;

use InvalidArgumentException;

/**
 * Configuración para Meta WhatsApp Cloud API
 * Lee y valida variables de entorno
 */
final class MetaWhatsAppCloudConfig
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $phoneNumberId,
        public readonly string $apiVersion,
        public readonly ?string $fromPhoneNumber = null,
    ) {
        $this->validate();
    }

    /**
     * Crea configuración desde variables de entorno
     */
    public static function fromEnv(): self
    {
        $accessToken = env('WHATSAPP_CLOUD_ACCESS_TOKEN');
        $phoneNumberId = env('WHATSAPP_CLOUD_PHONE_NUMBER_ID');
        $apiVersion = env('WHATSAPP_CLOUD_API_VERSION', 'v20.0');
        $fromPhoneNumber = env('WHATSAPP_CLOUD_FROM_PHONE_NUMBER');

        return new self(
            accessToken: $accessToken ?? '',
            phoneNumberId: $phoneNumberId ?? '',
            apiVersion: $apiVersion,
            fromPhoneNumber: $fromPhoneNumber
        );
    }

    /**
     * Valida que las credenciales requeridas estén presentes
     */
    private function validate(): void
    {
        if (empty($this->accessToken)) {
            throw new InvalidArgumentException(
                'WHATSAPP_CLOUD_ACCESS_TOKEN no está configurado. Agrega esta variable a tu archivo .env'
            );
        }

        if (empty($this->phoneNumberId)) {
            throw new InvalidArgumentException(
                'WHATSAPP_CLOUD_PHONE_NUMBER_ID no está configurado. Agrega esta variable a tu archivo .env'
            );
        }

        if (empty($this->apiVersion)) {
            throw new InvalidArgumentException(
                'WHATSAPP_CLOUD_API_VERSION no está configurado'
            );
        }
    }

    /**
     * Retorna la URL base de la API
     */
    public function getBaseUrl(): string
    {
        return "https://graph.facebook.com/{$this->apiVersion}";
    }

    /**
     * Retorna la URL del endpoint de mensajes
     */
    public function getMessagesEndpoint(): string
    {
        return "{$this->getBaseUrl()}/{$this->phoneNumberId}/messages";
    }
}

<?php

namespace App\Features\WhatsApp\Infrastructure\Meta;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente HTTP para Meta WhatsApp Cloud API
 * Wrapper de Laravel HTTP Client con configuración específica
 */
final class MetaWhatsAppCloudClient
{
    public function __construct(
        private readonly MetaWhatsAppCloudConfig $config
    ) {
    }

    /**
     * Envía un mensaje de texto
     *
     * @param string $to Número destino (solo dígitos, sin +)
     * @param string $message Texto del mensaje
     * @return array Respuesta de la API
     * @throws MetaWhatsAppCloudException
     */
    public function sendTextMessage(string $to, string $message): array
    {
        $url = $this->config->getMessagesEndpoint();
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];

        Log::info('Enviando mensaje WhatsApp', [
            'to' => $to,
            'url' => $url,
            'message_length' => strlen($message),
        ]);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->config->accessToken}",
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->json() ?? [];
            $requestId = $response->header('x-fb-request-id');

            if (!$response->successful()) {
                Log::error('Error al enviar mensaje WhatsApp', [
                    'status' => $statusCode,
                    'response' => $this->truncateResponse($responseBody),
                    'request_id' => $requestId,
                ]);

                throw MetaWhatsAppCloudException::fromHttpResponse(
                    $statusCode,
                    $responseBody,
                    $requestId
                );
            }

            Log::info('Mensaje WhatsApp enviado exitosamente', [
                'to' => $to,
                'message_id' => $responseBody['messages'][0]['id'] ?? 'unknown',
                'request_id' => $requestId,
            ]);

            return $responseBody;
        } catch (MetaWhatsAppCloudException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Excepción inesperada al enviar mensaje WhatsApp', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw new MetaWhatsAppCloudException(
                "Error de conexión o timeout: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Trunca la respuesta si es muy grande para logging
     */
    private function truncateResponse(array $response, int $maxLength = 500): array
    {
        $json = json_encode($response);
        if (strlen($json) > $maxLength) {
            return ['truncated' => true, 'length' => strlen($json)];
        }
        return $response;
    }
}

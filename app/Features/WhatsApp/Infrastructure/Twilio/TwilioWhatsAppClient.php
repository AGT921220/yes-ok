<?php

namespace App\Features\WhatsApp\Infrastructure\Twilio;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente HTTP para Twilio WhatsApp API
 * Wrapper de Laravel HTTP Client con configuración específica
 */
final class TwilioWhatsAppClient
{
    private $accountSid;
    private $authToken;
    private $whatsappFrom;
    private $statusCallbackUrl;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->whatsappFrom = config('services.twilio.whatsapp_from');
        $this->statusCallbackUrl = config('services.twilio.status_callback_url');
    }

    public function sendTemplateMessage(string $to, string $templateName, array $contentVariables = []): void
    {
        $url = $this->getUrl();
        // Twilio requiere formato whatsapp:+521... para el destino
        $toFormatted = $this->formatPhoneNumber($to);
        // dump($toFormatted);
        // dd($this->config->whatsappFrom);

        $payload = [
            'From' => $this->whatsappFrom,
            'To' => $toFormatted,
            'ContentSid' => $templateName,
            'ContentVariables' => json_encode($contentVariables, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT),
        ];


        Log::info('Enviando mensaje WhatsApp vía Twilio', [
            'to' => $toFormatted,
            'from' => $this->whatsappFrom,
            'content_sid' => $templateName,
            'content_variables_count' => count($contentVariables),
        ]);

        $url = $this->getUrl();


        info('Enviando mensaje WhatsApp vía Twilio...', [
            'to' => $toFormatted,
            'from' => $this->whatsappFrom,
            'contentSid' => $templateName,
            'contentVariables' => $contentVariables,
            'url' => $url,
        ]);

        // dd($url, $payload);
        $response = Http::withBasicAuth($this->accountSid, $this->authToken)
            ->asForm()
            ->post($url, $payload);

        if (! $response->successful()) {
            Log::error('Twilio WhatsApp API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'from' => $this->whatsappFrom,
                'to' => $toFormatted,
                'contentSid' => $templateName,
                'contentVariables' => $contentVariables,
            ]);

            throw new \RuntimeException('Error Twilio: ' . $response->body());
        }
    }

    /**
     * Formatea el número de teléfono para Twilio (agrega prefijo whatsapp: si no lo tiene)
     */
    private function formatPhoneNumber(string $phone): string
    {
        $phone = "521" . $phone;
        // Si ya tiene el prefijo whatsapp:, retornarlo tal cual
        if (str_starts_with($phone, 'whatsapp:')) {
            return $phone;
        }

        // Si tiene +, agregar whatsapp: prefix
        if (str_starts_with($phone, '+')) {
            return 'whatsapp:' . $phone;
        }

        // Si no tiene +, agregarlo primero
        return 'whatsapp:+' . $phone;
    }

    /**
     * Trunca la respuesta si es muy grande para logging
     */
    private function truncateResponse(array $response, int $maxLength = 500): array
    {
        $json = json_encode($response);
        if (strlen($json) > $maxLength) {
            return ['truncated' => true, 'length' => strlen($json), 'sid' => $response['sid'] ?? null];
        }
        return $response;
    }

    private function getUrl(): string
    {
        return "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
    }
}

<?php

namespace App\Features\Shared\Infrastructure\GoogleCalendar;

use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;

class CancelGoogleCalendarAppointment
{
    private string $clientId;
    private string $clientSecret;
    private string $refreshToken;
    private string $calendarId;

    public function __construct()
    {
        $this->clientId = (string) config('services.google.client_id');
        $this->clientSecret = (string) config('services.google.client_secret');
        $this->refreshToken = trim((string) config('services.google.refresh_token'));
        $this->calendarId = (string) config('services.google.calendar_id', 'primary');
        // info($this->clientId);
        // info($this->clientSecret);
        // info($this->refreshToken);
        // info($this->calendarId);
    }

    private function client(): GoogleClient
    {
        if (!$this->clientId || !$this->clientSecret) {
            throw new \RuntimeException('Falta GOOGLE_CLIENT_ID o GOOGLE_CLIENT_SECRET');
        }

        if (!$this->refreshToken) {
            throw new \RuntimeException('Falta GOOGLE_REFRESH_TOKEN');
        }

        $client = new GoogleClient();

        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);

        // MUY IMPORTANTE: mismo redirect usado al generar el refresh token
        $client->setRedirectUri(config('services.google.redirect_uri'));

        // scopes correctos
        $client->setScopes([
            Calendar::CALENDAR_EVENTS
        ]);

        $client->setAccessType('offline');

        // 🔴 Google requiere explícitamente este grant
        $token = $client->fetchAccessTokenWithRefreshToken($this->refreshToken);

        if (isset($token['error'])) {
            throw new \RuntimeException(
                'Google OAuth error: ' . json_encode($token, JSON_UNESCAPED_SLASHES)
            );
        }

        $client->setAccessToken($token);

        return $client;
    }

    public function __invoke($title, $start, $end, $description = null)
    {
        $service = new Calendar($this->client());

        $event = new Event([
            'summary' => $title,
            'description' => $description,
            'start' => [
                'dateTime' => $start,
                'timeZone' => 'America/Mexico_City',
            ],
            'end' => [
                'dateTime' => $end,
                'timeZone' => 'America/Mexico_City',
            ],
        ]);

        $created = $service->events->insert($this->calendarId, $event);
        return $created->getId();
    }
}

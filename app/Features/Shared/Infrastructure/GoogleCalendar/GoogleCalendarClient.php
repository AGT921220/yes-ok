<?php

namespace App\Features\Shared\Infrastructure\GoogleCalendar;

use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Exception as GoogleServiceException;

final class GoogleCalendarClient
{
    private Calendar $calendar;
    private string $calendarId;

    public function __construct()
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessType('offline');
        $client->setScopes([Calendar::CALENDAR_EVENTS]);

        $token = $client->fetchAccessTokenWithRefreshToken(
            trim(config('services.google.refresh_token'))
        );

        if (isset($token['error'])) {
            throw new \RuntimeException('Google OAuth error: ' . json_encode($token));
        }

        $client->setAccessToken($token);

        $this->calendar = new Calendar($client);
        $this->calendarId = config('services.google.calendar_id', 'primary');
    }

    public function createAppointment(
        string $title,
        string $startIso,
        string $endIso,
        ?string $description = null
    ): string {
        $event = new Event([
            'summary' => $title,
            'description' => $description,
            'start' => [
                'dateTime' => $startIso,
                'timeZone' => 'America/Mexico_City',
            ],
            'end' => [
                'dateTime' => $endIso,
                'timeZone' => 'America/Mexico_City',
            ],
        ]);

        $created = $this->calendar->events->insert($this->calendarId, $event);

        return (string) $created->getId();
    }

    public function updateAppointmentTime(
        string $eventId,
        string $startIso,
        string $endIso
    ): void {

        $event = new Event([
            'start' => [
                'dateTime' => $startIso,
                'timeZone' => 'America/Mexico_City',
            ],
            'end' => [
                'dateTime' => $endIso,
                'timeZone' => 'America/Mexico_City',
            ],
        ]);

        $this->calendar->events->patch(
            $this->calendarId,
            $eventId,
            $event
        );
    }
    public function cancelAppointment(string $eventId): void
    {
        $this->calendar->events->delete($this->calendarId, $eventId);
    }

    public function deleteAppointment(string $eventId): void
    {
        try {
            $this->calendar->events->delete($this->calendarId, $eventId);
        } catch (GoogleServiceException $e) {

            // Si el evento ya no existe en Google, no rompemos el flujo
            if ($e->getCode() === 410 || $e->getCode() === 404) {
                return;
            }

            throw $e;
        }
    }

    // public function updateAppointment(
    //     string $eventId,
    //     string $title,
    //     string $startIso,
    //     string $endIso,
    //     ?string $description = null
    // ): void {
    //     $event = $this->calendar->events->get($this->calendarId, $eventId);

    //     $event->setSummary($title);
    //     $event->setDescription($description);

    //     $event->setStart(new EventDateTime([
    //         'dateTime' => $startIso,
    //         'timeZone' => 'America/Mexico_City',
    //     ]));

    //     $event->setEnd(new EventDateTime([
    //         'dateTime' => $endIso,
    //         'timeZone' => 'America/Mexico_City',
    //     ]));

    //     $this->calendar->events->update($this->calendarId, $eventId, $event);
    // }
    public function getAppointments(?string $timeMin = null, ?string $timeMax = null): array
    {
        $params = [
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ];

        if ($timeMin) {
            $params['timeMin'] = $timeMin; // ISO8601
        }

        if ($timeMax) {
            $params['timeMax'] = $timeMax; // ISO8601
        }

        $events = $this->calendar->events->listEvents($this->calendarId, $params);

        $result = [];

        foreach ($events->getItems() as $event) {
            $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
            $end = $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate();

            $result[] = [
                'id' => $event->getId(),
                'summary' => $event->getSummary(),
                'description' => $event->getDescription(),
                'start' => $start,
                'end' => $end,
            ];
        }

        return $result;
    }
}

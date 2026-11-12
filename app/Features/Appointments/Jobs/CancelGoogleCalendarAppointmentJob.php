<?php

namespace App\Features\Appointments\Jobs;

use App\Features\Shared\Infrastructure\GoogleCalendar\GoogleCalendarClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CancelGoogleCalendarAppointmentJob implements ShouldQueue
{
    public const QUEUE = 'calendar';
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $googleCalendarId;
    public function __construct(string $googleCalendarId)
    {
        $this->googleCalendarId = $googleCalendarId;
    }
    public function handle(GoogleCalendarClient $googleCalendarClient): void
    {
        if ($this->googleCalendarId === '') {
            return;
        }

        try {
            $googleCalendarClient->deleteAppointment($this->googleCalendarId);
            Log::info('Google Calendar event cancelled', [
                'google_calendar_id' => $this->googleCalendarId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error cancelling Google Calendar event', [
                'google_calendar_id' => $this->googleCalendarId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

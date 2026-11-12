<?php

namespace App\Features\Appointments\Jobs;

use App\Features\Shared\Infrastructure\GoogleCalendar\CreateGoogleCalendarAppointment;
use App\Features\Shared\Infrastructure\GoogleCalendar\GoogleCalendarClient;
use App\Models\Appointment;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateGoogleCalendarAppointmentJob implements ShouldQueue
{
    public const QUEUE = 'calendar';
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $appointmentId;
    public function __construct(int $appointmentId)
    {
        $this->appointmentId = $appointmentId;
    }
    public function handle(GoogleCalendarClient $googleCalendarClient)
    {
        $appointment = Appointment::with('client')->where('id', $this->appointmentId)->first();

        $client = $appointment->client;

        $date = Carbon::parse($appointment->date)->toDateString();
        $startIso = Carbon::parse($date . ' ' . $appointment->start_time, 'America/Mexico_City')
            ->toIso8601String();
        $endIso = Carbon::parse($date . ' ' . $appointment->end_time, 'America/Mexico_City')
            ->toIso8601String();
        $calendarId = $googleCalendarClient->createAppointment(
            $client->full_name . "(" . $appointment->status->value . ")",
            $startIso,
            $endIso,
            'Cita para el cliente ' . $client->full_name
        );
        // $calendarId = $createGoogleCalendarAppointment->__invoke(
        //     $client->full_name . "(" . $appointment->status->value . ")",
        //     $startIso,
        //     $endIso,
        //     'Cita para el cliente ' . $client->full_name
        // );
        Appointment::where('id', $this->appointmentId)
            ->update(['google_calendar_id' => $calendarId]);
    }
    // private function logNotification(int $patientId, ?int $appointmentId, string $channel, string $template, string $message): void
    // {
    //     $now = Carbon::now();
    //     $log = new NotificationLog();
    //     $log->client_id = $patientId;
    //     $log->appointment_id = $appointmentId;
    //     $log->channel = $channel;
    //     $log->template_name = $template;
    //     $log->message = $message;
    //     $log->status = 'SANDBOX';
    //     $log->sent_at = $now;
    //     $log->save();
    // }
}

<?php

namespace App\Features\Appointments\Jobs;

use App\Models\Appointment;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAppointmentCreatedPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $appointmentId
    ) {}

    public function handle(PushNotificationService $pushService): void
    {
        $appointment = Appointment::with('patient.user')->find($this->appointmentId);
        if (! $appointment) {
            return;
        }
        $user = $appointment->client->user ?? null;
        if (! $user) {
            return;
        }
        $dateTimeLabel = $appointment->date->format('d/m/Y') . ' ' . (\Carbon\Carbon::parse($appointment->start_time)->format('H:i'));
        $pushService->sendAppointmentReminder($user, $appointment->id, $dateTimeLabel);
    }
}

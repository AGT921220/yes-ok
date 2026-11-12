<?php

namespace App\Features\Appointments\Jobs;

use App\Models\Appointment;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GenerateUpcomingAppointmentRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        info('Generando recordatorios para citas próximas...');
        info('Obteniendo citas próximas para el día siguiente...');
        foreach ($this->getAppointments() as $appointment) {
            RemindUpcomingAppointmentsViaWhatsAppJob::dispatch($appointment->id)->onQueue('whatsapp');
        }
        info('Recordatorios generados para ' . $this->getAppointments()->count() . ' citas próximas.');
    }

    private function getAppointments(): Collection
    {
        info('Obteniendo citas para el día siguiente...');
        $now = now();
        $tomorrow = now()->addDay()->toDateString();
                info('Fecha actual: ' . $now);
        info('Buscando citas para el día: ' . $tomorrow);
        return Appointment::where('date', $tomorrow)->with('patient.user')
        ->limit(3)
        ->get();
    }
}

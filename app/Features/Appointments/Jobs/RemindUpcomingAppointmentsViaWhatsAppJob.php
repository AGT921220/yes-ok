<?php

namespace App\Features\Appointments\Jobs;

use App\Features\WhatsApp\Application\UseCases\SendWhatsappTemplate;
use App\Features\WhatsApp\Domain\Templates\AppointmentConfirmationRequestTemplate;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RemindUpcomingAppointmentsViaWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $appointmentId;
    public function __construct(int $appointmentId)
    {
        $this->appointmentId = $appointmentId;
    }
    public function tags()
    {
        return [
            'event: ' . 'remind-upcomming-whatsapp',
            'eventName: ' . 'Remind Upcoming Appointments Via WhatsApp',
            'appointmentId: ' . $this->appointmentId,
            'clientPhone: ' . '6144950659',
            'clientName: ' . 'Alvaro Guilebaldo',
        ];
    }
    public function handle(SendWhatsappTemplate $sendWhatsappTemplate): void
    {
        $appointment = Appointment::with('client')->find($this->appointmentId);
        if (! $appointment) {
            info('Cita no encontrada para ID: ' . $this->appointmentId);
            return;
        }
        $user = $appointment->client ?? null;
        if (! $user) {
            info('Cliente no encontrado para la cita ID: ' . $this->appointmentId);
            return;
        }
        $date = Carbon::parse($appointment->date)->toDateString();
        $time = Carbon::parse($appointment->start_time)->format('g:i A');

        $template = new AppointmentConfirmationRequestTemplate(
            $appointment->id,
            'Alvaro Guilebaldo',
            $appointment->client->full_name,
            $date,
            $time,
            'https://maps.app.goo.gl/tjD1vgtRgCGfoSeL7',
            '55 1234 5678'
        );
        $phone = '8130990229';
        $sendWhatsappTemplate->__invoke($phone, $template);

        $phone = '6144950659';
        $sendWhatsappTemplate->__invoke($phone, $template);
    }
}

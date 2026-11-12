<?php

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendCancellationNotifications implements ShouldQueue
{
    /**
     * Solo se ejecuta si la cancelación fue exitosa (evento disparado después del commit).
     * Registra log de notificación y en el futuro puede enviar WhatsApp/Email.
     */
    public function handle(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;
        $appointment->load('client');

        $message = $event->byPatient
            ? "Cita cancelada por el cliente para el {$appointment->date->format('Y-m-d')} a las " . (\is_string($appointment->start_time) ? substr($appointment->start_time, 0, 5) : $appointment->start_time)
            : "Cita cancelada (motivo: " . ($appointment->cancellation_reason ?? 'N/A') . ") para el {$appointment->date->format('Y-m-d')}";

        try {
            DB::table('notification_logs')->insert([
                'client_id' => $appointment->client_id,
                'appointment_id' => $appointment->id,
                'channel' => 'WHATSAPP',
                'template_name' => 'cancellation',
                'message' => $message,
                'status' => 'SANDBOX',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SendCancellationNotifications: failed to write notification_log', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AvailabilityBlock;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AvailabilityBlockService
{
    /**
     * Crear bloqueo y marcar citas afectadas
     */
    public function create(array $data, User $user): AvailabilityBlock
    {
        $block = AvailabilityBlock::create([
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'is_full_day' => $data['is_full_day'] ?? true,
            'reason' => $data['reason'] ?? null,
            'is_recurring' => $data['is_recurring'] ?? false,
            'recurrence_pattern' => $data['recurrence_pattern'] ?? null,
            'created_by' => $user->id,
        ]);

        // Marcar citas afectadas
        $this->markAffectedAppointments($block);

        return $block;
    }

    /**
     * Marcar citas afectadas como REQUIERE_REAGENDAR
     */
    private function markAffectedAppointments(AvailabilityBlock $block): void
    {
        $query = Appointment::where('date', '>=', $block->start_date)
            ->where('date', '<=', $block->end_date)
            ->whereNotIn('status', [
                AppointmentStatus::CANCELADA->value,
                AppointmentStatus::COMPLETADA->value,
            ]);

        // Si no es día completo, filtrar por horario
        if (!$block->is_full_day && $block->start_time && $block->end_time) {
            $query->where(function ($q) use ($block) {
                $q->where('start_time', '<', $block->end_time)
                  ->where('end_time', '>', $block->start_time);
            });
        }

        $affected = $query->get();

        foreach ($affected as $apt) {
            $apt->update(['status' => AppointmentStatus::REQUIERE_REAGENDAR->value]);

            // Log notificación
            $client = Client::find($apt->client_id);
            if ($client) {
                $this->logNotification(
                    $client->id,
                    $apt->id,
                    'WHATSAPP',
                    'reschedule_needed',
                    "La cita de {$client->full_name} requiere reagendarse"
                );
            }
        }
    }

    /**
     * Log de notificación (sandbox mode)
     */
    private function logNotification(int $patientId, ?int $appointmentId, string $channel, string $template, string $message): void
    {
        DB::table('notification_logs')->insert([
            'client_id' => $patientId,
            'appointment_id' => $appointmentId,
            'channel' => $channel,
            'template_name' => $template,
            'message' => $message,
            'status' => 'SANDBOX',
            'sent_at' => now(),
        ]);
    }
}

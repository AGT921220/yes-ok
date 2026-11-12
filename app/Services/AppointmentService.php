<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentCancelled;
use App\Features\Appointments\Jobs\CreateGoogleCalendarAppointmentJob;
use App\Features\Appointments\Jobs\SendAppointmentCreatedPushJob;
use App\Features\Appointments\Jobs\UpdateGoogleCalendarAppointmentJob;
use App\Models\Appointment;
use App\Models\AvailabilityBlock;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use App\Support\BookingSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentService
{

    public function __construct() {}
    /**
     * Verificar si hay empalme con otra cita.
     *
     * Si $staffUserId no es null, solo cuenta citas del mismo personal (Alfredo a las 12 no bloquea a otro usuario).
     * Si es null, cuenta cualquier cita ese día (comportamiento conservador para citas sin personal asignado).
     */
    public function checkOverlap(string $date, string $startTime, string $endTime, ?int $excludeId = null, ?int $staffUserId = null): bool
    {
        $query = Appointment::where('date', $date)
            ->whereNotIn('status', [AppointmentStatus::CANCELADA->value]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($staffUserId !== null) {
            $query->where('staff_user_id', $staffUserId);
        }

        $existing = $query->get();

        foreach ($existing as $apt) {
            // start_time y end_time son TIME en MySQL, normalizar a formato H:i:s
            $aptStart = is_string($apt->start_time) ? $apt->start_time : Carbon::parse($apt->start_time)->format('H:i:s');
            $aptEnd = is_string($apt->end_time) ? $apt->end_time : Carbon::parse($apt->end_time)->format('H:i:s');

            // Normalizar tiempos de entrada también
            $startTimeNorm = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
            $endTimeNorm = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;

            if ($startTimeNorm < $aptEnd && $endTimeNorm > $aptStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Horarios de inicio disponibles (H:i) para una fecha, duración y personal (panel agenda).
     * Respeta ventana BOOKING_* o 24h si forzado; bloqueos globales; empalmes según staff (misma lógica que checkOverlap).
     *
     * @return list<string>
     */
    public function availableStartTimesForStaff(
        string $date,
        int $durationMinutes,
        ?int $staffUserId,
        ?int $excludeAppointmentId,
        bool $forced
    ): array {
        if ($durationMinutes <= 0) {
            return [];
        }

        $dateObj = Carbon::parse($date);
        if (! $forced && $dateObj->dayOfWeekIso >= 7) {
            return [];
        }

        $step = max(1, (int) config('booking.slot_step_minutes', 15));
        $workStart = BookingSchedule::workStartMinutes();
        $workEnd = BookingSchedule::workEndMinutes();

        $startMinutesList = [];
        if ($forced) {
            for ($m = 0; $m < 24 * 60; $m += $step) {
                $startMinutesList[] = $m;
            }
        } else {
            for ($m = $workStart; $m < $workEnd; $m += $step) {
                $startMinutesList[] = $m;
            }
        }

        $allowedEndMin = $forced ? (23 * 60 + 59) : $workEnd;
        $result = [];

        foreach ($startMinutesList as $startMin) {
            $slotEndMin = $startMin + $durationMinutes;
            if ($slotEndMin > $allowedEndMin) {
                continue;
            }

            $startTime = sprintf('%02d:%02d', intdiv($startMin, 60), $startMin % 60);
            $endTime = sprintf('%02d:%02d', intdiv($slotEndMin, 60), $slotEndMin % 60);

            if ($this->checkOverlap($date, $startTime, $endTime, $excludeAppointmentId, $staffUserId)) {
                continue;
            }
            if ($this->checkAvailabilityBlock($date, $startTime, $endTime)) {
                continue;
            }
            $result[] = $startTime;
        }

        return $result;
    }

    /**
     * Verificar si el horario está bloqueado
     */
    public function checkAvailabilityBlock(string $date, string $startTime, string $endTime): bool
    {
        $blocks = AvailabilityBlock::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get();

        foreach ($blocks as $block) {
            if ($block->is_full_day) {
                return true;
            }

            if ($block->start_time && $block->end_time) {
                $blockStart = is_string($block->start_time) ? $block->start_time : Carbon::parse($block->start_time)->format('H:i:s');
                $blockEnd = is_string($block->end_time) ? $block->end_time : Carbon::parse($block->end_time)->format('H:i:s');

                // Normalizar tiempos de entrada
                $startTimeNorm = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
                $endTimeNorm = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;

                if ($startTimeNorm < $blockEnd && $endTimeNorm > $blockStart) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validar horario permitido: L-S dentro de BOOKING_SCHEDULE_* (si no es cita forzada)
     */
    private function validateAllowedSchedule(string $date, string $startTime, string $endTime, bool $forced): void
    {
        if ($forced) {
            return;
        }

        $dateObj = Carbon::parse($date);
        // Lunes = 1, Sábado = 6, Domingo = 7
        if ($dateObj->dayOfWeekIso >= 7) {
            throw new \InvalidArgumentException(BookingSchedule::validationMessagePrefix());
        }

        $startNorm = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
        $endNorm = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;

        $windowStart = BookingSchedule::normalizedStart();
        $windowEnd = BookingSchedule::normalizedEnd();

        if ($startNorm < $windowStart) {
            throw new \InvalidArgumentException(BookingSchedule::validationMessagePrefix());
        }
        if ($endNorm > $windowEnd) {
            throw new \InvalidArgumentException(BookingSchedule::validationMessagePrefix());
        }
    }

    /**
     * Si vienen `service_ids`, calcula end_time como:
     * end_time = start_time + SUM(duraciones de los servicios)
     */
    private function resolveAppointmentTimes(array $data): array
    {
        $startTime = $data['start_time'] ?? null;
        $serviceIds = $data['service_ids'] ?? null;

        if (is_array($serviceIds) && !empty($serviceIds) && $startTime) {
            $totalMinutes = Service::totalDurationMinutesForIds($serviceIds);
            if ($totalMinutes <= 0) {
                throw new \InvalidArgumentException('Debe seleccionar al menos un servicio activo.');
            }

            $start = Carbon::parse($startTime);
            $end = $start->copy()->addMinutes($totalMinutes);
            $data['end_time'] = $end->format('H:i');
        }

        return $data;
    }

    /**
     * Sincroniza servicios en pivot.
     */
    private function validateAndSyncServices(Appointment $appointment, ?array $serviceIds): void
    {
        if ($serviceIds === null) {
            return; // no tocar relación
        }

        $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));

        // Si se mandó un array vacío, desconectar servicios.
        if (empty($serviceIds)) {
            $appointment->services()->detach();
            return;
        }

        $services = Service::whereIn('id', $serviceIds)->where('is_active', true)->get();
        if ($services->count() !== count($serviceIds)) {
            throw new \InvalidArgumentException('Uno o más servicios no existen o no están activos.');
        }

        foreach ($services as $s) {
            if (!in_array((int) $s->duration_minutes, Service::ALLOWED_DURATIONS, true)) {
                throw new \InvalidArgumentException(
                    "Duración no permitida para el servicio: {$s->name}. Use 15, 30, 45 o 60 minutos."
                );
            }
        }

        $sync = [];
        foreach (array_values($serviceIds) as $i => $id) {
            $sync[$id] = ['sort_order' => $i];
        }
        $appointment->services()->sync($sync);
    }

    /**
     * Crear cita con validaciones
     */
    public function create(array $data, ?User $user): Appointment
    {
        // Validar cliente existe
        Client::findOrFail($data['client_id']);

        $data = $this->resolveAppointmentTimes($data);

        // Validar no en el pasado
        $aptDateTime = Carbon::parse($data['date'] . ' ' . $data['start_time']);
        if ($aptDateTime->isPast()) {
            throw new \InvalidArgumentException('No se pueden crear citas en el pasado');
        }

        $forced = (bool) ($data['forced'] ?? false);
        $this->validateAllowedSchedule($data['date'], $data['start_time'], $data['end_time'], $forced);

        $staffForOverlap = $data['staff_user_id'] ?? null;

        // Validar empalme (por personal si hay staff asignado)
        if ($this->checkOverlap($data['date'], $data['start_time'], $data['end_time'], null, $staffForOverlap)) {
            throw new \InvalidArgumentException('Existe un empalme con otra cita');
        }

        // Validar bloqueo
        if ($this->checkAvailabilityBlock($data['date'], $data['start_time'], $data['end_time'])) {
            throw new \InvalidArgumentException('El horario está bloqueado');
        }

        if (empty($data['end_time']) || empty($data['start_time'])) {
            throw new \InvalidArgumentException('Se requieren hora de inicio y fin, o servicios para calcular la duración.');
        }

        $serviceIds = $data['service_ids'] ?? null;
        unset($data['service_ids']);

        // Crear cita
        $appointment = Appointment::create([
            'client_id' => $data['client_id'],
            'staff_user_id' => $data['staff_user_id'] ?? null,
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'] ?? AppointmentStatus::CONFIRMADA->value,
            'notes' => $data['notes'] ?? null,
            'reason' => $data['reason'] ?? null,
            'created_by' => $user?->id,
            'booked_by_client' => (bool) ($data['booked_by_client'] ?? false),
            'forced' => $forced,
            'attendance_status' => 'pendiente',
            'payment_status' => 'sin_pagar',
            'type' => $data['type'] ?? 'presencial',
            'google_calendar_id' => $data['google_calendar_id'] ?? null,
        ]);

        $this->validateAndSyncServices($appointment, $serviceIds);

        CreateGoogleCalendarAppointmentJob::dispatch($appointment->id)->onQueue(CreateGoogleCalendarAppointmentJob::QUEUE);
        SendAppointmentCreatedPushJob::dispatch($appointment->id);
        // Log notificación (sandbox)
        // $this->logNotification(
        //     $client->id,
        //     $appointment->id,
        //     'WHATSAPP',
        //     'appointment_created',
        //     "Nueva cita programada para {$client->full_name} el {$data['date']} a las {$data['start_time']}"
        // );

        return $appointment->load(['client', 'staff', 'services']);
    }

    /**
     * Actualizar cita con validaciones
     */
    public function update(Appointment $appointment, array $data, User $user): Appointment
    {
        $newDate = $data['date'] ?? $appointment->date;
        $dateStr = is_string($newDate) ? $newDate : $newDate->format('Y-m-d');

        $newStart = $data['start_time'] ?? (
            is_string($appointment->start_time)
                ? substr($appointment->start_time, 0, 5)
                : \Carbon\Carbon::parse($appointment->start_time)->format('H:i')
        );

        $forced = (bool) ($data['forced'] ?? $appointment->forced);

        $serviceIds = $data['service_ids'] ?? null;
        if (array_key_exists('service_ids', $data)) {
            unset($data['service_ids']);
        }

        // Si viene service_ids, se recalcula end_time con duración real.
        if ($serviceIds !== null && is_array($serviceIds) && !empty($serviceIds)) {
            $totalMinutes = Service::totalDurationMinutesForIds($serviceIds);
            if ($totalMinutes <= 0) {
                throw new \InvalidArgumentException('Debe seleccionar al menos un servicio activo.');
            }
            $newEnd = Carbon::parse($newStart)->addMinutes($totalMinutes)->format('H:i');
        } else {
            $newEnd = $data['end_time'] ?? (
                is_string($appointment->end_time)
                    ? substr($appointment->end_time, 0, 5)
                    : \Carbon\Carbon::parse($appointment->end_time)->format('H:i')
            );
        }

        $this->validateAllowedSchedule($dateStr, $newStart, $newEnd, $forced);

        $effectiveStaffId = array_key_exists('staff_user_id', $data)
            ? $data['staff_user_id']
            : $appointment->staff_user_id;

        if ($this->checkOverlap($dateStr, $newStart, $newEnd, $appointment->id, $effectiveStaffId)) {
            throw new \InvalidArgumentException('Existe un empalme con otra cita');
        }

        if ($this->checkAvailabilityBlock($dateStr, $newStart, $newEnd)) {
            throw new \InvalidArgumentException('El horario está bloqueado');
        }

        $oldDate = $appointment->date->format('Y-m-d');
        $oldStart = is_string($appointment->start_time) ? substr($appointment->start_time, 0, 5) : $appointment->start_time;
        $applyNewDate = ($oldDate !== $dateStr) || ($oldStart !== $newStart);

        $data['start_time'] = $newStart;
        $data['end_time'] = $newEnd;
        $data['forced'] = $forced;

        $appointment->update($data);

        $this->validateAndSyncServices($appointment, $serviceIds);

        if ($applyNewDate) {
            UpdateGoogleCalendarAppointmentJob::dispatch($appointment->id)->onQueue(UpdateGoogleCalendarAppointmentJob::QUEUE);
        }

        return $appointment->load(['client', 'staff', 'services']);
    }

    /**
     * Estados finales: no se puede cancelar de nuevo.
     */
    private static function nonCancellableStatuses(): array
    {
        return [
            AppointmentStatus::CANCELADA->value,
            AppointmentStatus::COMPLETADA->value,
        ];
    }

    /**
     * Cancelar cita de forma transaccional y atómica.
     * Libera el horario (al quedar CANCELADA, checkOverlap/available-slots ya no la consideran).
     * Dispara evento para notificaciones solo si la cancelación fue exitosa.
     *
     * @param  bool  $byPatient  true si cancela el cliente desde el portal
     * @return Appointment|null  Cita actualizada o null si ya estaba cancelada/completada (evita doble cancelación)
     */
    public function cancel(Appointment $appointment, string $cancellationReason, bool $byPatient = false): ?Appointment
    {
        return DB::transaction(function () use ($appointment, $cancellationReason, $byPatient) {
            $nonCancellable = self::nonCancellableStatuses();

            // Protección contra doble request: solo actualizar si no está en estado final
            $affected = Appointment::query()
                ->where('id', $appointment->id)
                ->whereNotIn('status', $nonCancellable)
                ->update([
                    'status' => AppointmentStatus::CANCELADA->value,
                    'cancellation_reason' => $cancellationReason,
                ]);

            if ($affected === 0) {
                Log::warning('Cancel attempt invalid: appointment already in final state', [
                    'appointment_id' => $appointment->id,
                    'current_status' => $appointment->status->value ?? $appointment->status,
                ]);
                return null;
            }

            $updated = $appointment->fresh();
            Log::info('Appointment cancelled', [
                'appointment_id' => $updated->id,
                'client_id' => $updated->client_id,
                'by_patient' => $byPatient,
            ]);

            // Notificaciones solo si la cancelación fue exitosa (después del commit implícito)
            event(new AppointmentCancelled($updated, $byPatient));

            return $updated;
        });
    }

    /**
     * Log de notificación (sandbox mode)
     */
}

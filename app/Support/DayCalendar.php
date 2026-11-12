<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DayCalendar
{
    public static function durations(): array
    {
        return [15, 30, 45, 60, 90, 120];
    }

    public static function duration(?int $chosen = null): int
    {
        if ($chosen !== null && $chosen >= 15 && $chosen <= 720) {
            return $chosen;
        }

        return 30;
    }

    /**
     * @param  Collection<int, \App\Models\AvailabilityBlock>  $blocks
     * @return array<string, list<array{full: bool, label: string, reason: ?string}>>
     */
    public static function blockMap(Collection $blocks, Carbon $start, Carbon $end): array
    {
        $map = [];
        foreach ($blocks as $block) {
            $cursor = $block->start_date->copy()->max($start);
            $last = $block->end_date->copy()->min($end);
            while ($cursor->lte($last)) {
                $label = $block->is_full_day
                    ? 'Día bloqueado'
                    : substr((string) $block->start_time, 0, 5).'–'.substr((string) $block->end_time, 0, 5);
                $map[$cursor->toDateString()][] = [
                    'full' => (bool) $block->is_full_day,
                    'label' => $label,
                    'reason' => $block->reason,
                ];
                $cursor->addDay();
            }
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public static function freeStarts(string $date, int $duration, bool $forced, AppointmentService $service, int $staffId): array
    {
        $starts = $service->availableStartTimesForStaff($date, $duration, $staffId, null, $forced);

        return array_values(array_filter($starts, function (string $time) use ($date) {
            return Carbon::parse($date.' '.$time)->isFuture();
        }));
    }

    public static function owner(): User
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::ADMIN, UserRole::ASISTENTE])
            ->orderByRaw("CASE WHEN role = 'ADMIN' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->firstOrFail();
    }

    public static function month(string $value): Carbon
    {
        return Carbon::parse($value.'-01')->startOfMonth();
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return list<list<array{date: string, day: int, in_month: bool, sunday: bool, today: bool, count: int}>>
     */
    public static function weeks(Carbon $month, Collection $appointments, bool $named = false): array
    {
        $counts = [];
        $labels = [];
        foreach ($appointments as $appointment) {
            $key = $appointment->date->toDateString();
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            if ($named) {
                $labels[$key][] = $appointment->client?->first_name ?: 'Cita';
            }
        }

        $cursor = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];

        while ($cursor->lte($end)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();
                $week[] = [
                    'date' => $key,
                    'day' => $cursor->day,
                    'in_month' => $cursor->month === $month->month,
                    'sunday' => $cursor->dayOfWeekIso === 7,
                    'today' => $cursor->isToday(),
                    'count' => $counts[$key] ?? 0,
                    'labels' => array_slice($labels[$key] ?? [], 0, 2),
                    'more' => max(0, ($counts[$key] ?? 0) - 2),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * @param  Collection<int, Appointment>  $dayAppointments
     * @return list<array{time: string, appointment: ?Appointment, busy: bool}>
     */
    public static function rows(string $date, Collection $dayAppointments, bool $forced, AppointmentService $service, int $staffId): array
    {
        $day = Carbon::parse($date);
        if (! $forced && $day->dayOfWeekIso === 7) {
            return [];
        }

        $duration = self::duration();
        $free = array_flip($service->availableStartTimesForStaff($date, $duration, $staffId, null, $forced));
        $start = $forced ? 0 : BookingSchedule::workStartMinutes();
        $end = $forced ? 24 * 60 : BookingSchedule::workEndMinutes();
        $rows = [];

        for ($minute = $start; $minute + $duration <= $end; $minute += $duration) {
            $time = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
            $slotEnd = sprintf('%02d:%02d', intdiv($minute + $duration, 60), ($minute + $duration) % 60);
            $hit = $dayAppointments->first(function (Appointment $appointment) use ($time, $slotEnd) {
                $startTime = substr((string) $appointment->start_time, 0, 5);
                $endTime = substr((string) $appointment->end_time, 0, 5);

                return $time < $endTime && $slotEnd > $startTime;
            });
            $past = Carbon::parse($date.' '.$time)->isPast();

            $rows[] = [
                'time' => $time,
                'appointment' => $hit && substr((string) $hit->start_time, 0, 5) === $time ? $hit : null,
                'busy' => $past || $hit !== null || ! isset($free[$time]),
            ];
        }

        return $rows;
    }
}

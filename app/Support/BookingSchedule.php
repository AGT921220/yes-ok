<?php

namespace App\Support;

class BookingSchedule
{
    public static function normalizedStart(): string
    {
        return self::normalizeTimeString((string) config('booking.schedule_start', '12:00'));
    }

    public static function normalizedEnd(): string
    {
        return self::normalizeTimeString((string) config('booking.schedule_end', '20:00'));
    }

    public static function workStartMinutes(): int
    {
        return self::timeToMinutes(self::normalizedStart());
    }

    public static function workEndMinutes(): int
    {
        return self::timeToMinutes(self::normalizedEnd());
    }

    public static function humanRange(): string
    {
        return sprintf(
            '%s–%s',
            trim((string) config('booking.schedule_start', '12:00')),
            trim((string) config('booking.schedule_end', '20:00'))
        );
    }

    public static function validationMessagePrefix(): string
    {
        return sprintf(
            'Horario no permitido. Disponible de lunes a sábado de %s a %s. Use "Forzar Cita" si aplica.',
            trim((string) config('booking.schedule_start', '12:00')),
            trim((string) config('booking.schedule_end', '20:00'))
        );
    }

    private static function normalizeTimeString(string $t): string
    {
        $t = trim($t);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $t, $m)) {
            return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
        }
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $t, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return '12:00:00';
    }

    private static function timeToMinutes(string $hms): int
    {
        $parts = explode(':', $hms);

        return ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));
    }
}

<?php

namespace App\Console\Commands;

use App\Features\Shared\Infrastructure\GoogleCalendar\GoogleCalendarClient;
use App\Models\Appointment;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportGoogleCalendarAppointmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-google-calendar-appointments-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa eventos de Google Calendar como citas; omite no-consultas (cumpleaños, pagos, etc.) y reutiliza cliente si ya existe.';

    /**
     * Títulos que indican que el evento NO es una cita de consulta (se omiten).
     */
    private static function isNonConsultationEvent(string $summary): bool
    {
        $lower = mb_strtolower($summary, 'UTF-8');
        $skipPatterns = [
            'cumpleaños',
            'cumpleanos',
            'cumple',
            'pagar',
            'pago de',
            'pago ',
            'pagos',
            'reunión',
            'reunion',
            'reuniones',
            'vacaciones',
            'permiso',
            'feriado',
            'bloqueado',
            'no disponible',
            'almuerzo',
            'comida',
            'cita cancelada',
            'cancelada',
            'canclado',
            'cancelado',
            'se acaba',
            'se acaban',
            'citas se acaban',
            'recordatorio',
            'recordatorios',
            'reservado',
            'reservada',
            'personal',
            'privado',
            'cierre',
            'horario cerrado',
            'no laboral',
            'suspensión',
            'suspension',
            'audiencia',
            'audiencias',
            'neuraxpharm',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliza nombre a mayúscula inicial por palabra (ej. "yuliana" → "Yuliana", "JOSÉ" → "José").
     */
    private static function normalizeNameCase(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return $trimmed;
        }

        return mb_convert_case($trimmed, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Execute the console command.
     */
    public function handle(GoogleCalendarClient $googleCalendarClient): int
    {
        $start = now()->startOfMonth()->toIso8601String();
        $end = now()->endOfMonth()->addYear()->toIso8601String();

        $appointments = $googleCalendarClient->getAppointments($start, $end);
        $result = $this->persistGoogleAppointments($appointments, 1);

        $this->info('Guardadas: ' . count($result['saved']) . ', omitidas: ' . count($result['skipped']));

        return self::SUCCESS;
    }

    public function persistGoogleAppointments(array $googleAppointments, ?int $createdByUserId = null): array
    {
        $saved = [];
        $skipped = [];

        $nextPlaceholderInt = null;

        foreach ($googleAppointments as $item) {
            $googleEventId = trim((string) ($item['id'] ?? ''));
            $summary = trim((string) ($item['summary'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $startIso = trim((string) ($item['start'] ?? ''));
            $endIso = trim((string) ($item['end'] ?? ''));

            if ($googleEventId === '' || $summary === '' || $startIso === '' || $endIso === '') {
                $skipped[] = ['reason' => 'missing_fields', 'google_calendar_id' => $googleEventId ?: null];
                continue;
            }

            if (self::isNonConsultationEvent($summary)) {
                $skipped[] = ['reason' => 'non_consultation', 'google_calendar_id' => $googleEventId, 'summary' => $summary];
                continue;
            }

            if (Appointment::query()->where('google_calendar_id', $googleEventId)->exists()) {
                $skipped[] = ['reason' => 'appointment_exists', 'google_calendar_id' => $googleEventId];
                continue;
            }

            $fullName = preg_replace('/\s+/', ' ', $summary);
            $parts = array_values(array_filter(explode(' ', $fullName)));

            $firstName = $fullName;
            $paternalSurname = '';
            $maternalSurname = null;

            if (count($parts) >= 3) {
                $maternalSurname = array_pop($parts);
                $paternalSurname = array_pop($parts);
                $firstName = implode(' ', $parts);
            } elseif (count($parts) === 2) {
                $paternalSurname = array_pop($parts);
                $firstName = implode(' ', $parts);
            }

            $firstName = self::normalizeNameCase($firstName);
            $paternalSurname = self::normalizeNameCase($paternalSurname);
            $maternalSurname = $maternalSurname !== null ? self::normalizeNameCase($maternalSurname) : null;

            $whatsapp = null;
            if ($description !== '') {
                if (preg_match('/(\+?\d[\d\s\-\(\)]{7,}\d)/', $description, $m)) {
                    $digits = preg_replace('/\D+/', '', $m[1]);
                    if (strlen($digits) >= 8) {
                        $whatsapp = strlen($digits) > 10 ? substr($digits, -10) : $digits;
                    }
                } else {
                    $digits = preg_replace('/\D+/', '', $description);
                    if (strlen($digits) >= 8) {
                        $whatsapp = strlen($digits) > 10 ? substr($digits, -10) : $digits;
                    }
                }
            }

            $client = $this->findExistingPatient($whatsapp, $firstName, $paternalSurname, $maternalSurname);

            if (!$client) {
                if (!$whatsapp) {
                    if ($nextPlaceholderInt === null) {
                        $last = Client::query()
                            ->where('whatsapp', 'like', '00000%')
                            ->orderByDesc('whatsapp')
                            ->value('whatsapp');
                        $nextPlaceholderInt = $last ? ((int) substr((string) $last, 5)) + 1 : 1;
                    }
                    do {
                        $candidate = '00000' . str_pad((string) $nextPlaceholderInt, 5, '0', STR_PAD_LEFT);
                        $nextPlaceholderInt++;
                    } while (Client::query()->where('whatsapp', $candidate)->exists());
                    $whatsapp = $candidate;
                }

                $client = new Client();
                $client->first_name = $firstName;
                $client->paternal_surname = $paternalSurname;
                $client->maternal_surname = $maternalSurname;
                $client->whatsapp = $whatsapp;
                $client->save();
            }

            $start = Carbon::parse($startIso);
            $end = Carbon::parse($endIso);
            $date = $start->toDateString();
            $startTime = $start->format('H:i:s');
            $endTime = $end->format('H:i:s');

            $appointment = new Appointment();
            $appointment->google_calendar_id = $googleEventId;
            $appointment->client_id = $client->id;
            $appointment->date = $date;
            $appointment->start_time = $startTime;
            $appointment->end_time = $endTime;
            $appointment->status = 'PENDIENTE';
            $appointment->notes = $description !== '' ? $description : null;
            $appointment->reason = null;
            $appointment->created_by = $createdByUserId;
            $appointment->booked_by_client = false;
            $appointment->save();

            $saved[] = [
                'google_calendar_id' => $googleEventId,
                'client_id' => $client->id,
                'appointment_id' => $appointment->id,
                'whatsapp' => $client->whatsapp,
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }

        return [
            'saved' => $saved,
            'skipped' => $skipped,
        ];
    }

    /**
     * Busca un cliente existente por teléfono (prioritario) o por nombre + apellidos.
     * Así la misma persona con varias citas en distintos días/semanas/meses reutiliza un solo cliente.
     */
    private function findExistingPatient(?string $whatsapp, string $firstName, string $paternalSurname, ?string $maternalSurname): ?Client
    {
        if ($whatsapp !== null && $whatsapp !== '') {
            $byPhone = Client::query()->where('whatsapp', $whatsapp)->first();
            if ($byPhone) {
                return $byPhone;
            }
        }

        $q = Client::query()
            ->where('first_name', $firstName)
            ->where('paternal_surname', $paternalSurname);

        if ($maternalSurname === null || $maternalSurname === '') {
            $q->whereNull('maternal_surname');
        } else {
            $q->where('maternal_surname', $maternalSurname);
        }

        return $q->first();
    }
}

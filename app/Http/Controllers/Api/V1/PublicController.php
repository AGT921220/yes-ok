<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneNormalizer;
use App\Models\Client;
use App\Models\Appointment;
use App\Models\User;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PublicController extends Controller
{
    /**
     * Personal disponible para agendar sin registro (activo, admin o asistente).
     */
    public function bookingStaff(): JsonResponse
    {
        $users = User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::ADMIN, UserRole::ASISTENTE])
            ->with('services')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $users->map(static fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'service_ids' => $u->services->pluck('id')->values()->all(),
            ]),
        ]);
    }

    /**
     * Horarios disponibles para una fecha (misma lógica que el panel: por personal, bloqueos, ventana BOOKING_*).
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'target_date' => 'required|date|after_or_equal:today',
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'staff_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $durationMinutes = (int) $request->duration_minutes;
        if ($durationMinutes % 15 !== 0) {
            throw ValidationException::withMessages([
                'duration_minutes' => ['La duración debe ser múltiplo de 15 minutos.'],
            ]);
        }

        $staffUserId = $request->filled('staff_user_id') ? (int) $request->staff_user_id : null;
        if ($staffUserId !== null) {
            $this->assertPublicBookableStaff($staffUserId);
        }

        $targetDate = Carbon::parse($request->target_date)->startOfDay();

        if ($targetDate->dayOfWeekIso >= 7) {
            return response()->json(['data' => []]);
        }

        $starts = app(AppointmentService::class)->availableStartTimesForStaff(
            $targetDate->toDateString(),
            $durationMinutes,
            $staffUserId,
            null,
            false
        );

        $slots = [];
        foreach ($starts as $start) {
            $startC = Carbon::createFromFormat('H:i', $start);
            $endC = $startC->copy()->addMinutes($durationMinutes);
            $slots[] = [
                'date' => $targetDate->toDateString(),
                'start_time' => $start,
                'end_time' => $endC->format('H:i'),
                'available' => true,
            ];
        }

        return response()->json(['data' => $slots]);
    }

    /**
     * Crear cita sin registro (guest booking)
     * Busca o crea cliente automáticamente
     */
    public function bookAppointment(Request $request): JsonResponse
    {
        // Rate limiting por IP y teléfono
        $ip = $request->ip();
        $phoneKey = 'booking_attempts_' . md5($ip . ($request->input('phone') ?? ''));
        
        $attempts = Cache::get($phoneKey, 0);
        if ($attempts >= 5) {
            return response()->json([
                'message' => 'Demasiados intentos. Por favor, intente más tarde.'
            ], 429);
        }
        Cache::put($phoneKey, $attempts + 1, 3600); // 1 hora
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'paternal_surname' => 'required|string|max:255',
            'maternal_surname' => 'nullable|string|max:255',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'staff_user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => ['required_without:service_ids', 'nullable', 'date_format:H:i', 'after:start_time'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'reason' => 'nullable|string|max:500',
        ]);

        $staffUser = $this->assertPublicBookableStaff((int) $request->staff_user_id);

        $serviceIds = $request->input('service_ids', []);
        $staffUser->load('services');
        $allowedServiceIds = $staffUser->services->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($allowedServiceIds !== []) {
            foreach ($serviceIds as $sid) {
                if (! in_array((int) $sid, $allowedServiceIds, true)) {
                    throw ValidationException::withMessages([
                        'service_ids' => ['Uno o más servicios no los atiende el profesional seleccionado.'],
                    ]);
                }
            }
        }

        try {
            // Normalizar teléfono a E.164
            $normalizedPhone = PhoneNormalizer::normalizeMexicanPhone($request->phone);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'phone' => [$e->getMessage()],
            ]);
        }

        $appointmentDate = Carbon::parse($request->date)->startOfDay();
        $startTimeCarbon = Carbon::createFromFormat('H:i', $request->start_time);
        $startTime = $startTimeCarbon->format('H:i');
        $endTime = $request->filled('end_time')
            ? Carbon::createFromFormat('H:i', $request->end_time)->format('H:i')
            : null;

        // Validar que no sea en el pasado
        $appointmentDateTime = $appointmentDate->copy()->setTime($startTimeCarbon->hour, $startTimeCarbon->minute, 0);
        if ($appointmentDateTime->isPast()) {
            throw ValidationException::withMessages([
                'date' => ['No se pueden crear citas en el pasado'],
            ]);
        }

        return DB::transaction(function () use ($request, $normalizedPhone, $appointmentDate, $startTime, $endTime) {
            // Buscar cliente existente por teléfono normalizado
            $client = Client::where('whatsapp', $normalizedPhone)->first();
            
            if (!$client) {
                // Si viene email, buscar también por email (pero teléfono tiene prioridad)
                if ($request->email) {
                    $client = Client::where('email', $request->email)->first();
                }
            }
            
            if (!$client) {
                // Crear nuevo cliente (guest/prospect)
                $client = Client::create([
                    'first_name' => $request->first_name,
                    'paternal_surname' => $request->paternal_surname,
                    'maternal_surname' => $request->maternal_surname,
                    'whatsapp' => $normalizedPhone,
                    'email' => $request->email,
                    'consent_notifications' => true,
                    'otp_verified' => false,
                ]);
            } else {
                // Actualizar datos si son diferentes (solo si vienen)
                $updateData = [];
                if ($request->has('first_name') && $client->first_name !== $request->first_name) {
                    $updateData['first_name'] = $request->first_name;
                }
                if ($request->has('paternal_surname') && $client->paternal_surname !== $request->paternal_surname) {
                    $updateData['paternal_surname'] = $request->paternal_surname;
                }
                if ($request->has('maternal_surname') && $client->maternal_surname !== $request->maternal_surname) {
                    $updateData['maternal_surname'] = $request->maternal_surname;
                }
                if ($request->email && $client->email !== $request->email) {
                    $updateData['email'] = $request->email;
                }
                if (!empty($updateData)) {
                    $client->update($updateData);
                }
            }

            $serviceIds = $request->input('service_ids');

            // Crear cita delegando validaciones y sincronización a AppointmentService
            $appointment = app(AppointmentService::class)->create([
                'client_id' => $client->id,
                'staff_user_id' => (int) $request->staff_user_id,
                'date' => $appointmentDate->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => AppointmentStatus::CONFIRMADA->value,
                'reason' => $request->reason,
                'created_by' => null,
                'booked_by_client' => true,
                'forced' => false,
                'type' => 'presencial',
                'service_ids' => $serviceIds,
            ], null);

            // Limpiar rate limit en caso de éxito
            Cache::forget('booking_attempts_' . md5($request->ip() . $request->input('phone')));

            return response()->json([
                'data' => [
                    'id' => $appointment->id,
                    'client' => [
                        'id' => $client->id,
                        'full_name' => $client->full_name,
                    ],
                    'date' => $appointment->date->format('Y-m-d'),
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'status' => $appointment->status->value,
                ],
                'message' => 'Cita agendada exitosamente',
            ], 201);
        });
    }

    public function requestClientAccess(Request $request): JsonResponse
    {
        $request->validate([
            'whatsapp' => 'required|string',
        ]);

        try {
            $normalizedPhone = PhoneNormalizer::normalizeMexicanPhone($request->whatsapp);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'whatsapp' => [$e->getMessage()],
            ]);
        }

        $client = Client::where('whatsapp', $normalizedPhone)->first();

        if (!$client) {
            // No revelar si el cliente existe o no por seguridad
            return response()->json([
                'message' => 'Si existe una cuenta con este WhatsApp, recibirás un enlace de acceso',
            ]);
        }

        // Generar token de acceso seguro (24 horas)
        $token = bin2hex(random_bytes(32));
        $client->access_token = $token;
        $client->access_token_expires = now()->addHours(24);
        $client->save();

        // Log notificación (sandbox mode)
        $magicLink = "/portal?token={$token}";
        DB::table('notification_logs')->insert([
            'client_id' => $client->id,
            'appointment_id' => null,
            'channel' => 'WHATSAPP',
            'template_name' => 'magic_link',
            'message' => "Accede a tu portal de cliente: {$magicLink}",
            'status' => 'SANDBOX',
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Si existe una cuenta con este WhatsApp, recibirás un enlace de acceso',
            // En modo sandbox, retornar el token para testing
            'sandbox_token' => $token,
            'sandbox_link' => $magicLink,
        ]);
    }

    public function getClientPortal(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $client = Client::where('access_token', $request->token)->first();

        if (!$client) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Token inválido o expirado',
                ],
            ], 401);
        }

        // Verificar expiración del token
        if ($client->access_token_expires && $client->access_token_expires->isPast()) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Token expirado. Solicita un nuevo enlace.',
                ],
            ], 401);
        }

        // Obtener citas (NO datos clínicos)
        $today = Carbon::today();

        $upcoming = Appointment::where('client_id', $client->id)
            ->where('date', '>=', $today)
            ->where('status', '!=', AppointmentStatus::CANCELADA->value)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $past = Appointment::where('client_id', $client->id)
            ->where('date', '<', $today)
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'id' => $client->id,
                'first_name' => $client->first_name,
                'paternal_surname' => $client->paternal_surname,
                'maternal_surname' => $client->maternal_surname,
                'full_name' => $client->full_name,
                'whatsapp' => $client->whatsapp,
                'email' => $client->email,
                'upcoming_appointments' => $upcoming->map(function ($apt) {
                    return [
                        'id' => $apt->id,
                        'date' => $apt->date->format('Y-m-d'),
                        'start_time' => $apt->start_time,
                        'end_time' => $apt->end_time,
                        'status' => $apt->status->value,
                        'reason' => $apt->reason,
                        'staff_user_id' => $apt->staff_user_id,
                    ];
                }),
                'past_appointments' => $past->map(function ($apt) {
                    return [
                        'id' => $apt->id,
                        'date' => $apt->date->format('Y-m-d'),
                        'start_time' => $apt->start_time,
                        'end_time' => $apt->end_time,
                        'status' => $apt->status->value,
                        'reason' => $apt->reason,
                    ];
                }),
            ],
        ]);
    }

    public function cancelAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $client = Client::where('access_token', $request->token)->first();

        if (!$client) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Token inválido',
                ],
            ], 401);
        }

        if ($client->access_token_expires && $client->access_token_expires->isPast()) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Token expirado',
                ],
            ], 401);
        }

        // Verificar que la cita pertenece al cliente
        if ($appointment->client_id !== $client->id) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Cita no encontrada',
                ],
            ], 404);
        }

        $reason = 'Cancelado por el cliente desde el portal';
        $service = app(AppointmentService::class);
        $updated = $service->cancel($appointment, $reason, true);

        if ($updated === null) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'APPOINTMENT_CANNOT_BE_CANCELLED',
                    'message' => 'Esta cita no puede ser cancelada',
                ],
            ], 409);
        }

        if ($updated->google_calendar_id) {
            \App\Features\Appointments\Jobs\CancelGoogleCalendarAppointmentJob::dispatch($updated->google_calendar_id)
                ->onQueue(\App\Features\Appointments\Jobs\CancelGoogleCalendarAppointmentJob::QUEUE);
        }

        return response()->json(['message' => 'Cita cancelada exitosamente']);
    }

    public function rescheduleAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'new_date' => 'required|date|after_or_equal:today',
            'new_start_time' => 'required|date_format:H:i',
            'new_end_time' => 'required|date_format:H:i|after:new_start_time',
        ]);

        $client = Client::where('access_token', $request->token)->first();

        if (!$client) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'Token inválido',
                ],
            ], 401);
        }

        if ($client->access_token_expires && $client->access_token_expires->isPast()) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Token expirado',
                ],
            ], 401);
        }

        // Verificar que la cita pertenece al cliente
        if ($appointment->client_id !== $client->id) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Cita no encontrada',
                ],
            ], 404);
        }

        if (in_array($appointment->status, [AppointmentStatus::CANCELADA, AppointmentStatus::COMPLETADA])) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'APPOINTMENT_CANNOT_BE_RESCHEDULED',
                    'message' => 'Esta cita no puede ser reagendada',
                ],
            ], 400);
        }

        $newDate = Carbon::parse($request->new_date)->startOfDay();
        $newStartTime = Carbon::createFromFormat('H:i', $request->new_start_time);
        $newEndTime = Carbon::createFromFormat('H:i', $request->new_end_time);

        // Si la cita tiene servicios asociados, recalcular end_time con duración real.
        $appointment->load('services');
        if ($appointment->services->isNotEmpty()) {
            $totalMinutes = (int) $appointment->services->sum('duration_minutes');
            $newEndTime = $newStartTime->copy()->addMinutes($totalMinutes);
        }

        // Validar que no sea en el pasado
        $appointmentDateTime = $newDate->copy()->setTime($newStartTime->hour, $newStartTime->minute, 0);
        if ($appointmentDateTime->isPast()) {
            throw ValidationException::withMessages([
                'new_date' => ['No se pueden crear citas en el pasado'],
            ]);
        }

        // Validar empalme (excluyendo la cita actual)
        $appointmentService = app(AppointmentService::class);
        if ($appointmentService->checkOverlap(
            $newDate->toDateString(),
            $newStartTime->format('H:i'),
            $newEndTime->format('H:i'),
            $appointment->id,
            $appointment->staff_user_id
        )) {
            throw ValidationException::withMessages([
                'new_start_time' => ['El horario seleccionado ya no está disponible'],
            ]);
        }

        // Validar bloqueo
        if ($appointmentService->checkAvailabilityBlock($newDate->toDateString(), $newStartTime->format('H:i'), $newEndTime->format('H:i'))) {
            throw ValidationException::withMessages([
                'new_date' => ['El horario está bloqueado'],
            ]);
        }

        // Actualizar cita
        $appointment->date = $newDate->toDateString();
        $appointment->start_time = $newStartTime->format('H:i:s');
        $appointment->end_time = $newEndTime->format('H:i:s');
        $appointment->status = AppointmentStatus::PENDIENTE;
        $appointment->save();

        // Log notificación
        DB::table('notification_logs')->insert([
            'client_id' => $client->id,
            'appointment_id' => $appointment->id,
            'channel' => 'WHATSAPP',
            'template_name' => 'reschedule',
            'message' => "Cita reagendada para el {$newDate->format('Y-m-d')} a las {$newStartTime->format('H:i')}",
            'status' => 'SANDBOX',
            'sent_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $appointment->id,
                'date' => $appointment->date->format('Y-m-d'),
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'status' => $appointment->status->value,
            ],
        ]);
    }

    private function assertPublicBookableStaff(int $userId): User
    {
        $user = User::query()
            ->where('id', $userId)
            ->where('is_active', true)
            ->whereIn('role', [UserRole::ADMIN, UserRole::ASISTENTE])
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'staff_user_id' => ['El profesional seleccionado no está disponible para reservas.'],
            ]);
        }

        return $user;
    }
}

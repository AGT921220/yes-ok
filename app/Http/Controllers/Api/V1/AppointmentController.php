<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAttendanceRequest;
use App\Http\Requests\Appointment\StorePaymentRequest;
use App\Http\Requests\Appointment\CancelAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Enums\AppointmentStatus;
use App\Features\Appointments\Jobs\CancelGoogleCalendarAppointmentJob;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService
    ) {}

    /**
     * Listar citas con filtros
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Appointment::with([
            'client',
            'staff',
            'services',
            'payments' => fn ($q) => $q->with('paymentType'),
        ]);

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Por defecto, excluir canceladas del listado usado por la agenda/calendario
            $query->where('status', '!=', AppointmentStatus::CANCELADA->value);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $appointments = $query->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    /**
     * Obtener cola de reagendamiento
     */
    public function rescheduleQueue(): AnonymousResourceCollection
    {
        $appointments = Appointment::with(['client', 'staff', 'services'])
            ->where('status', AppointmentStatus::REQUIERE_REAGENDAR->value)
            ->orderBy('date')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    /**
     * Horarios de inicio libres según duración, fecha, personal y bloqueos (tiempo real en el panel).
     */
    public function availabilitySlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'staff_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'exclude_appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'forced' => ['sometimes', 'boolean'],
        ]);

        $slots = $this->appointmentService->availableStartTimesForStaff(
            $validated['date'],
            (int) $validated['duration_minutes'],
            isset($validated['staff_user_id']) ? (int) $validated['staff_user_id'] : null,
            isset($validated['exclude_appointment_id']) ? (int) $validated['exclude_appointment_id'] : null,
            (bool) ($validated['forced'] ?? false)
        );

        return response()->json([
            'data' => [
                'slots' => $slots,
            ],
        ]);
    }

    /**
     * Crear nueva cita
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            info('appointmentController@store');
            $appointment = $this->appointmentService->create(
                $request->validated(),
                auth()->user()
            );
            DB::commit();
            return (new AppointmentResource($appointment))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            info($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener cita por ID
     */
    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);
        $appointment->load(['client', 'staff', 'services', 'payments' => fn ($q) => $q->with('paymentType')]);
        return new AppointmentResource($appointment);
    }

    /**
     * Actualizar estado de asistencia
     */
    public function updateAttendance(UpdateAttendanceRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);
        $appointment->update(['attendance_status' => $request->validated('status')]);
        $appointment->load('client');
        return response()->json(['data' => new AppointmentResource($appointment)]);
    }

    /**
     * Listar pagos de la cita
     */
    public function payments(Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);
        $payments = $appointment->payments()->with('paymentType')->orderBy('paid_at', 'desc')->get();
        return response()->json(['data' => $payments]);
    }

    /**
     * Eliminar (soft-delete) un pago de la cita
     */
    public function destroyPayment(Appointment $appointment, AppointmentPayment $payment): JsonResponse
    {
        $this->authorize('update', $appointment);

        if ($payment->appointment_id !== $appointment->id) {
            return response()->json(['message' => 'El pago no pertenece a esta cita'], 404);
        }

        $payment->delete();

         // No hay pagos activos después de eliminar: marcar la cita como sin pago
        $appointment->update(['payment_status' => 'sin_pago']);

        return response()->json(['message' => 'Pago eliminado correctamente']);
    }

    /**
     * Registrar un pago
     */
    public function storePayment(StorePaymentRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        if ($appointment->payments()->exists()) {
            return response()->json([
                'message' => 'Esta consulta ya cuenta con un pago registrado',
            ], 422);
        }

        $data = $request->validated();

        $payment = $appointment->payments()->create([
            'amount' => $data['amount'],
            'payment_type_id' => $data['payment_type_id'],
            'method' => $request->method,
            'notes' => $data['notes'] ?? null,
            'paid_at' => $data['paid_at'] ?? now(),
            'created_by' => auth()->id(),
        ]);

        $appointment->update(['payment_status' => 'pagado']);
        $appointment->load(['client', 'payments']);

        return response()->json(['data' => $payment], 201);
    }

    /**
     * Actualizar cita
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse|AppointmentResource
    {
        $this->authorize('update', $appointment);
        try {
            $appointment = $this->appointmentService->update(
                $appointment,
                $request->validated(),
                auth()->user()
            );

            return new AppointmentResource($appointment);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancelar cita (status CANCELADA + motivo). Transaccional y atómico.
     * 409 si ya está cancelada/completada; 200 con appointment actualizada si ok.
     */
    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        $updated = $this->appointmentService->cancel(
            $appointment,
            $request->validated('cancellation_reason'),
            false
        );

        if ($updated === null) {
            return response()->json([
                'success' => false,
                'message' => 'La cita ya está cancelada o no puede ser cancelada',
            ], 409);
        }

        $updated->load(['client', 'payments']);
        $this->dispatchCancelGoogleCalendarAppointment($appointment->google_calendar_id);

        return response()->json([
            'success' => true,
            'message' => 'Cita cancelada correctamente',
            'data' => new AppointmentResource($updated),
        ]);
    }

    /**
     * Eliminar cita (soft delete)
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorize('delete', $appointment);
        $this->dispatchCancelGoogleCalendarAppointment($appointment->google_calendar_id);
        $appointment->delete();

        return response()->json([
            'message' => 'Cita eliminada'
        ]);
    }
    private function dispatchCancelGoogleCalendarAppointment(?string $googleCalendarId = null):void
    {
        if ($googleCalendarId === null) {
            return;
        }
        CancelGoogleCalendarAppointmentJob::dispatch($googleCalendarId)->onQueue(CancelGoogleCalendarAppointmentJob::QUEUE);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\CancelAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAttendanceRequest;
use App\Models\Appointment;
use App\Models\AvailabilityBlock;
use App\Models\Client;
use App\Services\AppointmentService;
use App\Support\DayCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function __construct(private AppointmentService $appointments) {}

    public function index(Request $request)
    {
        $view = in_array($request->input('view'), ['month', 'week', 'day'], true) ? $request->input('view') : 'month';
        $selected = $request->input('date', now()->toDateString());
        $selectedDay = Carbon::parse($selected);
        $month = $view === 'month'
            ? DayCalendar::month($request->input('month', $selectedDay->format('Y-m')))
            : $selectedDay->copy()->startOfMonth();
        if ($view === 'month') {
            $selected = $this->selectedDate($request, $month);
            $selectedDay = Carbon::parse($selected);
        }

        $duration = DayCalendar::duration($request->integer('duration') ?: null);
        $forced = $request->boolean('forced') && $request->user()->role === UserRole::ADMIN;
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $weekStart = $selectedDay->copy()->startOfWeek(Carbon::MONDAY);

        $appointments = Appointment::with('client')
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->where('status', '!=', AppointmentStatus::CANCELADA)
            ->orderBy('start_time')
            ->get();

        $blocks = AvailabilityBlock::query()
            ->where('start_date', '<=', $gridEnd->toDateString())
            ->where('end_date', '>=', $gridStart->toDateString())
            ->get();

        $dayAppointments = $appointments->filter(
            fn (Appointment $appointment) => $appointment->date->toDateString() === $selected
        )->values();

        return view('agenda.index', [
            'view' => $view,
            'month' => $month,
            'selected' => $selected,
            'weekStart' => $weekStart,
            'weeks' => DayCalendar::weeks($month, $appointments, true),
            'blocked' => DayCalendar::blockMap($blocks, $gridStart, $gridEnd),
            'dayAppointments' => $dayAppointments,
            'weekAppointments' => $appointments->filter(
                fn (Appointment $appointment) => $appointment->date->between($weekStart, $weekStart->copy()->endOfWeek(Carbon::SUNDAY))
            ),
            'starts' => DayCalendar::freeStarts($selected, $duration, $forced, $this->appointments, $request->user()->id),
            'clients' => Client::orderBy('first_name')->get(),
            'durations' => DayCalendar::durations(),
            'duration' => $duration,
            'forced' => $forced,
            'closed' => $selectedDay->dayOfWeekIso === 7 && ! $forced,
            'draft' => $request->only(['client_id', 'first_name', 'paternal_surname', 'whatsapp', 'email', 'reason', 'start_time']),
        ]);
    }

    public function store(Request $request)
    {
        $this->trimTime($request, 'start_time');
        $digits = preg_replace('/\D/', '', (string) $request->input('whatsapp', ''));
        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }
        $request->merge(['whatsapp' => $digits !== '' ? $digits : null]);

        $data = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id', 'required_without:first_name'],
            'first_name' => ['nullable', 'string', 'max:255', 'required_without:client_id'],
            'paternal_surname' => ['nullable', 'string', 'max:255', 'required_with:first_name'],
            'maternal_surname' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'required_without:client_id', 'regex:/^\d{10}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', 'min:15', 'max:720'],
            'view' => ['nullable', 'in:month,week,day'],
            'reason' => ['nullable', 'string', 'max:500'],
            'forced' => ['sometimes', 'boolean'],
        ]);

        $clientId = $data['client_id'] ?? null;
        if (! $clientId) {
            $clientId = Client::create([
                'first_name' => $data['first_name'],
                'paternal_surname' => $data['paternal_surname'],
                'maternal_surname' => $data['maternal_surname'] ?? null,
                'whatsapp' => $data['whatsapp'],
                'email' => $data['email'] ?? null,
                'consent_notifications' => true,
            ])->id;
        }

        $forced = $request->boolean('forced') && $request->user()->role === UserRole::ADMIN;
        $end = Carbon::createFromFormat('H:i', $data['start_time'])->addMinutes((int) $data['duration'])->format('H:i');

        try {
            $this->appointments->create([
                'client_id' => (int) $clientId,
                'staff_user_id' => $request->user()->id,
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $end,
                'status' => AppointmentStatus::CONFIRMADA->value,
                'reason' => $data['reason'] ?? null,
                'created_by' => $request->user()->id,
                'booked_by_client' => false,
                'forced' => $forced,
                'type' => 'presencial',
            ], $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('agenda.index', [
                'month' => substr($data['date'], 0, 7),
                'date' => $data['date'],
                'view' => $data['view'] ?? 'month',
                'duration' => $data['duration'],
            ])
            ->with('success', 'Cita agendada.');
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        $appointment->load('client');

        return view('agenda.show', ['appointment' => $appointment]);
    }

    public function confirm(Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        $appointment->update(['status' => AppointmentStatus::CONFIRMADA]);

        return back()->with('success', 'Cita confirmada.');
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        $updated = $this->appointments->cancel($appointment, $request->validated('cancellation_reason'));

        if ($updated === null) {
            return back()->with('error', 'La cita ya está cancelada o no puede cancelarse.');
        }

        return back()->with('success', 'Cita cancelada.');
    }

    public function attendance(UpdateAttendanceRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        $appointment->update(['attendance_status' => $request->validated('status')]);

        return back()->with('success', 'Asistencia actualizada.');
    }

    private function selectedDate(Request $request, Carbon $month): string
    {
        $selected = $request->input('date', now()->toDateString());
        if (Carbon::parse($selected)->format('Y-m') !== $month->format('Y-m')) {
            return $month->isSameMonth(now()) ? now()->toDateString() : $month->toDateString();
        }

        return $selected;
    }

    private function trimTime(Request $request, string $field): void
    {
        $value = (string) $request->input($field, '');
        if (preg_match('/^(\d{2}:\d{2})/', $value, $matches)) {
            $request->merge([$field => $matches[1]]);
        }
    }
}

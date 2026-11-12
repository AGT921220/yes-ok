<?php

namespace App\Http\Controllers\Web;

use App\Enums\AppointmentStatus;
use App\Helpers\PhoneNormalizer;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Services\AppointmentService;
use App\Support\DayCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BookingPageController extends Controller
{
    public function __construct(private AppointmentService $appointments) {}

    public function create(Request $request)
    {
        $month = DayCalendar::month($request->input('month', now()->format('Y-m')));
        $selected = $request->input('date', $month->isSameMonth(now()) ? now()->toDateString() : $month->toDateString());
        if (Carbon::parse($selected)->format('Y-m') !== $month->format('Y-m')) {
            $selected = $month->toDateString();
        }

        $owner = DayCalendar::owner();
        $monthAppointments = Appointment::query()
            ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->where('status', '!=', AppointmentStatus::CANCELADA)
            ->orderBy('start_time')
            ->get();

        $dayAppointments = $monthAppointments->filter(
            fn (Appointment $appointment) => $appointment->date->toDateString() === $selected
        )->values();

        return view('booking.create', [
            'month' => $month,
            'selected' => $selected,
            'weeks' => DayCalendar::weeks($month, $monthAppointments),
            'rows' => DayCalendar::rows($selected, $dayAppointments, false, $this->appointments, $owner->id),
            'duration' => DayCalendar::duration(),
            'start' => $request->input('start'),
            'closed' => Carbon::parse($selected)->dayOfWeekIso === 7,
        ]);
    }

    public function store(Request $request)
    {
        $this->trimTime($request);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'paternal_surname' => ['required', 'string', 'max:255'],
            'maternal_surname' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $cacheKey = 'booking_attempts_'.md5($request->ip().$data['phone']);
        if (Cache::get($cacheKey, 0) >= 5) {
            return back()->withInput()->with('error', 'Demasiados intentos. Por favor, intente más tarde.');
        }
        Cache::put($cacheKey, Cache::get($cacheKey, 0) + 1, 3600);

        try {
            $phone = PhoneNormalizer::normalizeMexicanPhone($data['phone']);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['phone' => $e->getMessage()]);
        }

        $client = Client::where('whatsapp', $phone)->first();
        if (! $client && ! empty($data['email'])) {
            $client = Client::where('email', $data['email'])->first();
        }
        if (! $client) {
            $client = Client::create([
                'first_name' => $data['first_name'],
                'paternal_surname' => $data['paternal_surname'],
                'maternal_surname' => $data['maternal_surname'] ?? null,
                'whatsapp' => $phone,
                'email' => $data['email'] ?? null,
                'consent_notifications' => true,
            ]);
        }

        $end = Carbon::createFromFormat('H:i', $data['start_time'])->addMinutes(DayCalendar::duration())->format('H:i');

        try {
            $this->appointments->create([
                'client_id' => $client->id,
                'staff_user_id' => DayCalendar::owner()->id,
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $end,
                'status' => AppointmentStatus::CONFIRMADA->value,
                'reason' => $data['reason'] ?? null,
                'booked_by_client' => true,
                'forced' => false,
                'type' => 'presencial',
            ], null);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('booking.create', ['month' => substr($data['date'], 0, 7), 'date' => $data['date']])
            ->with('success', 'Cita agendada.');
    }

    private function trimTime(Request $request): void
    {
        $value = (string) $request->input('start_time', '');
        if (preg_match('/^(\d{2}:\d{2})/', $value, $matches)) {
            $request->merge(['start_time' => $matches[1]]);
        }
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Helpers\PhoneNormalizer;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PortalPageController extends Controller
{
    public function __construct(private AppointmentService $appointments) {}

    public function login()
    {
        return view('portal.login');
    }

    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'required_without:email'],
            'password' => ['required', 'string'],
        ]);

        $user = null;
        if (! empty($data['email'])) {
            $user = User::where('email', $data['email'])->where('role', UserRole::CLIENTE)->first();
        }

        if (! $user && ! empty($data['phone'])) {
            try {
                $phone = PhoneNormalizer::normalizeMexicanPhone($data['phone']);
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['phone' => $e->getMessage()])->withInput();
            }
            $user = User::where('phone', $phone)->where('role', UserRole::CLIENTE)->first();
        }

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active) {
            return back()->withErrors(['email' => 'Credenciales incorrectas'])->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.index');
    }

    public function index()
    {
        $client = auth()->user()->client;
        $appointments = $client
            ? Appointment::with('services')->where('client_id', $client->id)->orderByDesc('date')->get()
            : collect();

        return view('portal.index', compact('client', 'appointments'));
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $client = $request->user()->client;
        if (! $client || (int) $appointment->client_id !== (int) $client->id) {
            abort(403);
        }

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        $updated = $this->appointments->cancel($appointment, $data['cancellation_reason'], true);
        if ($updated === null) {
            return back()->with('error', 'Esta cita no se puede cancelar.');
        }

        return back()->with('success', 'Cita cancelada.');
    }
}

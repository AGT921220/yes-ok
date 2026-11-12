<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\ClientResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoints para el usuario autenticado con rol PACIENTE.
 * Solo devuelve/actualiza datos del cliente asociado al user (auth()->user()->client).
 */
class MeController extends Controller
{
    /**
     * Obtener el cliente asociado al usuario autenticado.
     * 403 si el user no tiene cliente (no debería ocurrir en flujo normal).
     */
    private function getPatient()
    {
        $client = auth()->user()->client;
        if (!$client) {
            abort(403, 'No tienes un perfil de cliente asociado.');
        }
        return $client;
    }

    /**
     * GET /me/profile - Perfil del cliente (solo lectura de sus datos).
     */
    public function profile(): JsonResponse
    {
        $client = $this->getPatient();
        return response()->json(['data' => new ClientResource($client)]);
    }

    /**
     * PUT /me/profile - Actualizar perfil (campos permitidos: email, consent_notifications).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $client = $this->getPatient();
        $validated = Validator::make($request->all(), [
            'email' => ['nullable', 'email', 'max:255'],
            'consent_notifications' => ['sometimes', 'boolean'],
        ])->validate();
        $client->update($validated);
        return response()->json(['data' => new ClientResource($client->fresh())]);
    }

    /**
     * GET /me/appointments - Listado de citas del cliente (siempre filtrado por client_id).
     */
    public function appointments(Request $request): AnonymousResourceCollection
    {
        $client = $this->getPatient();
        $query = Appointment::where('client_id', $client->id)->with('client');

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->orderBy('date')->orderBy('start_time')->get();
        return AppointmentResource::collection($appointments);
    }

    /**
     * GET /me/appointments/{appointment} - Detalle de una cita (solo si es del cliente).
     */
    public function showAppointment(Appointment $appointment): JsonResponse|AppointmentResource
    {
        $client = $this->getPatient();
        if ((int) $appointment->client_id !== (int) $client->id) {
            abort(403, 'No tienes permiso para ver esta cita.');
        }
        $appointment->load(['client', 'payments']);
        return new AppointmentResource($appointment);
    }
}

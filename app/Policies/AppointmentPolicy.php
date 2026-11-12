<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Ver cita: staff siempre; cliente solo la propia.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::ASISTENTE) {
            return true;
        }
        if ($user->role === UserRole::CLIENTE) {
            $client = $user->client;
            return $client && (int) $appointment->client_id === (int) $client->id;
        }
        return false;
    }

    /**
     * Actualizar/eliminar: solo staff (cliente no puede por reglas de negocio).
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::ASISTENTE;
    }

    /**
     * Eliminar: solo staff.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::ASISTENTE;
    }
}

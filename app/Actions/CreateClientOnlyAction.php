<?php

namespace App\Actions;

use App\Exceptions\ConflictError;
use App\Helpers\PhoneNormalizer;
use App\Models\Client;
use App\Models\User;

/**
 * Crea solo el registro de cliente (sin usuario). El usuario se puede crear después con "Agregar contraseña".
 */
class CreateClientOnlyAction
{
    public function execute(array $patientData): Client
    {
        $whatsapp = isset($patientData['whatsapp']) && $patientData['whatsapp'] !== '' && $patientData['whatsapp'] !== null
            ? $patientData['whatsapp']
            : null;
        $email = isset($patientData['email']) && $patientData['email'] !== '' && $patientData['email'] !== null
            ? $patientData['email']
            : null;

        if ($whatsapp === null && $email === null) {
            throw new \InvalidArgumentException('Debe proporcionar teléfono o correo.');
        }

        if ($whatsapp !== null) {
            $cleaned = preg_replace('/\D/', '', $whatsapp);
            if (strlen($cleaned) !== 10) {
                throw new \InvalidArgumentException('El número debe tener exactamente 10 dígitos (México).');
            }
            $whatsapp = strlen($cleaned) > 10 ? substr($cleaned, -10) : $cleaned;
            $normalizedPhone = PhoneNormalizer::normalizeMexicanPhone($whatsapp);
            if (Client::where('whatsapp', $whatsapp)->exists()) {
                throw new ConflictError('Ya existe un cliente con este teléfono.');
            }
            if (User::where('phone', $normalizedPhone)->exists()) {
                throw new ConflictError('Ya existe un usuario con este teléfono.');
            }
        }

        if ($email !== null) {
            $email = trim($email);
            if (Client::where('email', $email)->exists()) {
                throw new ConflictError('Ya existe un cliente con este correo.');
            }
            if (User::where('email', $email)->exists()) {
                throw new ConflictError('Ya existe un usuario con este correo.');
            }
        }

        return Client::create([
            'first_name' => $patientData['first_name'],
            'paternal_surname' => $patientData['paternal_surname'],
            'maternal_surname' => $patientData['maternal_surname'] ?? null,
            'whatsapp' => $whatsapp,
            'email' => $email,
            'user_id' => null,
            'consent_notifications' => $patientData['consent_notifications'] ?? true,
            'otp_verified' => false,
        ]);
    }
}

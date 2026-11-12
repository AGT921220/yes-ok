<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Exceptions\ConflictError;
use App\Helpers\PhoneNormalizer;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Action para crear un cliente con su usuario asociado.
 * 
 * Responsabilidades:
 * - Validar datos del cliente
 * - Crear User con role=CLIENTE
 * - Generar password segura
 * - Crear Client asociado
 * - Todo dentro de transacción
 * 
 * @package App\Actions
 */
class CreateClientWithUserAction
{
    /**
     * Ejecutar la acción de crear cliente con usuario.
     * 
     * @param array $patientData Datos del cliente (first_name, paternal_surname, etc.)
     * @param string|null $email Email opcional para login del cliente
     * @return array ['client' => Client, 'user' => User, 'password' => string]
     * @throws ConflictError Si ya existe un cliente con ese WhatsApp o email
     */
    public function execute(array $patientData, ?string $email = null): array
    {
        return DB::transaction(function () use ($patientData, $email) {
            // 1. Validar que no exista cliente con ese WhatsApp
            $existingPatient = Client::where('whatsapp', $patientData['whatsapp'])->first();
            if ($existingPatient) {
                throw new ConflictError(
                    "Ya existe un cliente con este WhatsApp: {$existingPatient->full_name}"
                );
            }

            // 2. Teléfono: solo 10 dígitos (México), ya validado por StoreClientRequest
            $cleaned = preg_replace('/\D/', '', $patientData['whatsapp']);
            if (strlen($cleaned) !== 10) {
                throw new \InvalidArgumentException('El número debe tener exactamente 10 dígitos (México).');
            }
            $normalizedPhone = PhoneNormalizer::normalizeMexicanPhone($cleaned);

            // 3. Validar email único si se proporciona
            if ($email) {
                $existingUser = User::where('email', $email)->first();
                if ($existingUser) {
                    throw new ConflictError(
                        "Ya existe un usuario con este email: {$email}"
                    );
                }
            }

            // 4. Validar phone único
            $existingUserByPhone = User::where('phone', $normalizedPhone)->first();
            if ($existingUserByPhone) {
                throw new ConflictError(
                    "Ya existe un usuario con este teléfono: {$normalizedPhone}"
                );
            }

            // 5. Contraseña: la proporciona quien crea el cliente o se genera una segura
            $passwordProvided = !empty($patientData['password']);
            $password = $passwordProvided
                ? $patientData['password']
                : $this->generateSecurePassword();

            // 6. Crear nombre completo para el usuario
            $fullName = trim(
                $patientData['first_name'] . ' ' . 
                $patientData['paternal_surname'] . 
                ($patientData['maternal_surname'] ? ' ' . $patientData['maternal_surname'] : '')
            );

            // 7. Crear User con role=PACIENTE
            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'phone' => $normalizedPhone,
                'password' => Hash::make($password),
                'role' => UserRole::CLIENTE,
                'is_active' => true,
            ]);

            // 8. Crear Client asociado
            $client = Client::create([
                'first_name' => $patientData['first_name'],
                'paternal_surname' => $patientData['paternal_surname'],
                'maternal_surname' => $patientData['maternal_surname'] ?? null,
                'whatsapp' => $patientData['whatsapp'],
                'email' => $email,
                'user_id' => $user->id,
                'consent_notifications' => $patientData['consent_notifications'] ?? true,
                'otp_verified' => false,
            ]);

            return [
                'client' => $client,
                'user' => $user,
                'password' => $passwordProvided ? null : $password, // Solo se devuelve si fue generada
                'password_generated' => !$passwordProvided,
            ];
        });
    }

    /**
     * Genera una contraseña segura aleatoria (12-16 caracteres).
     *
     * @return string Contraseña sin caracteres confusos (0, O, I, l, 1)
     */
    private function generateSecurePassword(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $length = random_int(12, 16);
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}

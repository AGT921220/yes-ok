<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstNames = ['Juan', 'María', 'Carlos', 'Ana', 'Luis', 'Laura', 'Pedro', 'Carmen', 'José', 'Patricia'];
        $paternalSurnames = ['García', 'Rodríguez', 'López', 'Martínez', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores'];
        $maternalSurnames = ['Hernández', 'Díaz', 'Morales', 'Cruz', 'Ortiz', 'Ramos', 'Jiménez', 'Ruiz', 'Vargas', 'Mendoza'];

        return [
            'first_name' => fake()->randomElement($firstNames),
            'paternal_surname' => fake()->randomElement($paternalSurnames),
            'maternal_surname' => fake()->optional(0.7)->randomElement($maternalSurnames),
            'whatsapp' => '+52' . fake()->numerify('##########'), // Formato E.164 para México
            'email' => fake()->optional(0.6)->safeEmail(),
            'consent_notifications' => fake()->boolean(80), // 80% de probabilidad
            'otp_verified' => fake()->boolean(60), // 60% de probabilidad
            'access_token' => null,
            'access_token_expires' => null,
        ];
    }

    /**
     * Indicate that the patient has verified OTP.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'otp_verified' => true,
        ]);
    }

    /**
     * Indicate that the patient has not consented to notifications.
     */
    public function withoutConsent(): static
    {
        return $this->state(fn (array $attributes) => [
            'consent_notifications' => false,
        ]);
    }
}

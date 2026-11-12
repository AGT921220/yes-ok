<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = fake()->numberBetween(9, 17); // Entre 9 AM y 5 PM
        $startMinute = fake()->randomElement([0, 15, 30, 45]);
        $duration = fake()->randomElement([30, 45, 60]); // Duración en minutos

        $startTime = sprintf('%02d:%02d', $startHour, $startMinute);
        $endTime = date('H:i', strtotime("{$startTime} +{$duration} minutes"));

        $date = fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d');

        return [
            'client_id' => Client::factory(),
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => fake()->randomElement([
                AppointmentStatus::PENDIENTE->value,
                AppointmentStatus::CONFIRMADA->value,
                AppointmentStatus::PENDIENTE->value, // Más probabilidad de pendiente
            ]),
            'notes' => fake()->optional(0.4)->sentence(),
            'reason' => fake()->optional(0.5)->sentence(),
            'created_by' => User::factory(),
            'booked_by_client' => false,
            'type' => 'presencial',
        ];
    }

    /**
     * Indicate that the appointment is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::CONFIRMADA->value,
        ]);
    }

    /**
     * Indicate that the appointment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::CANCELADA->value,
        ]);
    }

    /**
     * Indicate that the appointment requires rescheduling.
     */
    public function requiresReschedule(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::REQUIERE_REAGENDAR->value,
        ]);
    }

    /**
     * Indicate that the appointment is virtual.
     */
    public function virtual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'virtual',
        ]);
    }

    /**
     * Indicate that the appointment was booked by the patient.
     */
    public function bookedByPatient(): static
    {
        return $this->state(fn (array $attributes) => [
            'booked_by_client' => true,
            'created_by' => null,
        ]);
    }

    /**
     * Indicate that the appointment is in the past.
     */
    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $date = fake()->dateTimeBetween('-2 months', '-1 day')->format('Y-m-d');
            return [
                'date' => $date,
                'status' => fake()->randomElement([
                    AppointmentStatus::COMPLETADA->value,
                    AppointmentStatus::CANCELADA->value,
                    AppointmentStatus::NO_SHOW->value,
                ]),
            ];
        });
    }

    /**
     * Indicate that the appointment is today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->format('Y-m-d'),
            'status' => AppointmentStatus::CONFIRMADA->value,
        ]);
    }
}

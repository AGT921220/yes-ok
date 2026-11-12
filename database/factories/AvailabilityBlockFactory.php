<?php

namespace Database\Factories;

use App\Models\AvailabilityBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityBlock>
 */
class AvailabilityBlockFactory extends Factory
{
    protected $model = AvailabilityBlock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+2 months');
        $isFullDay = fake()->boolean(70); // 70% de probabilidad de día completo

        if ($isFullDay) {
            $endDate = fake()->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');
            $startTime = null;
            $endTime = null;
        } else {
            $endDate = $startDate;
            $startHour = fake()->numberBetween(9, 14);
            $endHour = fake()->numberBetween($startHour + 1, 18);
            $startTime = sprintf('%02d:00', $startHour);
            $endTime = sprintf('%02d:00', $endHour);
        }

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_full_day' => $isFullDay,
            'reason' => fake()->randomElement([
                'Vacaciones',
                'Día festivo',
                'Capacitación',
                'Reunión médica',
                'Emergencia familiar',
                'Mantenimiento',
            ]),
            'is_recurring' => false,
            'recurrence_pattern' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the block is a full day.
     */
    public function fullDay(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('now', '+2 months');
            $endDate = fake()->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');
            
            return [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'start_time' => null,
                'end_time' => null,
                'is_full_day' => true,
            ];
        });
    }

    /**
     * Indicate that the block is partial (time range).
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $date = fake()->dateTimeBetween('now', '+2 months');
            $startHour = fake()->numberBetween(9, 14);
            $endHour = fake()->numberBetween($startHour + 1, 18);
            
            return [
                'start_date' => $date->format('Y-m-d'),
                'end_date' => $date->format('Y-m-d'),
                'start_time' => sprintf('%02d:00', $startHour),
                'end_time' => sprintf('%02d:00', $endHour),
                'is_full_day' => false,
            ];
        });
    }
}

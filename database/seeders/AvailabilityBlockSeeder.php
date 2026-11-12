<?php

namespace Database\Seeders;

use App\Models\AvailabilityBlock;
use App\Models\User;
use Illuminate\Database\Seeder;

class AvailabilityBlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No hay usuarios. Ejecuta DatabaseSeeder primero.');
            return;
        }

        // Crear algunos bloqueos de día completo
        AvailabilityBlock::factory(5)
            ->fullDay()
            ->state(function (array $attributes) use ($users) {
                return [
                    'created_by' => $users->random()->id,
                ];
            })
            ->create();

        // Crear algunos bloqueos parciales (rangos de tiempo)
        AvailabilityBlock::factory(3)
            ->partial()
            ->state(function (array $attributes) use ($users) {
                return [
                    'created_by' => $users->random()->id,
                ];
            })
            ->create();

        $this->command->info("Creados " . AvailabilityBlock::count() . " bloqueos de disponibilidad");
    }
}

<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();
        $users = User::all();

        if ($clients->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No hay clientes o usuarios. Ejecuta ClientSeeder y DatabaseSeeder primero.');
            return;
        }

        // Crear citas futuras (pendientes y confirmadas)
        Appointment::factory(15)
            ->state(function (array $attributes) use ($clients, $users) {
                return [
                    'client_id' => $clients->random()->id,
                    'created_by' => $users->random()->id,
                ];
            })
            ->create();

        // Crear algunas citas confirmadas
        Appointment::factory(10)
            ->confirmed()
            ->state(function (array $attributes) use ($clients, $users) {
                return [
                    'client_id' => $clients->random()->id,
                    'created_by' => $users->random()->id,
                ];
            })
            ->create();

        // Crear citas pasadas (completadas, canceladas, no show)
        Appointment::factory(8)
            ->past()
            ->state(function (array $attributes) use ($clients, $users) {
                return [
                    'client_id' => $clients->random()->id,
                    'created_by' => $users->random()->id,
                ];
            })
            ->create();

        // Crear algunas citas que requieren reagendamiento
        Appointment::factory(3)
            ->requiresReschedule()
            ->state(function (array $attributes) use ($clients, $users) {
                return [
                    'client_id' => $clients->random()->id,
                    'created_by' => $users->random()->id,
                ];
            })
            ->create();

        // Crear algunas citas agendadas por clientes
        Appointment::factory(5)
            ->bookedByPatient()
            ->state(function (array $attributes) use ($clients) {
                return [
                    'client_id' => $clients->random()->id,
                ];
            })
            ->create();

        $this->command->info("Creadas " . Appointment::count() . " citas de prueba");
    }
}

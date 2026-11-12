<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 20 clientes de prueba
        $clients = Client::factory(20)->create();

        // Crear algunos clientes verificados
        Client::factory(5)->verified()->create();

        // Crear algunos clientes sin consentimiento
        Client::factory(3)->withoutConsent()->create();

        $this->command->info("Creados " . Client::count() . " clientes de prueba");
    }
}

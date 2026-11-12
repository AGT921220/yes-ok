<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seeders...');

        $admin = $this->setAdminUser();
        // 1. Crear usuarios
        $this->seedUsers($admin);

        // 2. Crear clientes
        $this->call(ClientSeeder::class);

        // 3. Crear citas
        $this->call(AppointmentSeeder::class);

        // 4. Crear bloqueos de disponibilidad
        $this->call(AvailabilityBlockSeeder::class);

        // 5. Catálogo de servicios
        $this->call(ServiceSeeder::class);

        // 6. Catálogo de tipos de pago
        $this->call(PaymentTypeSeeder::class);

        $this->command->info('');
        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('📋 Credenciales de acceso:');
        $this->command->info('   Admin: admin@yes-ok.com / admin123');
        $this->command->info('   Asistentes: Ver usuarios creados en la base de datos');
    }

    /**
     * Seed users (admin and assistants)
     */
    private function seedUsers(User $admin): void
    {
        // Crear usuario admin principal (idempotente)

        if ($admin->wasRecentlyCreated) {
            $this->command->info('✅ Usuario admin creado: admin@yes-ok.com / admin123');
        } else {
            $this->command->info('ℹ️  Usuario admin ya existe: admin@yes-ok.com');
        }

        // Crear usuarios asistentes
        $assistantsCount = User::where('role', 'ASISTENTE')->count();
        if ($assistantsCount < 5) {
            $toCreate = 5 - $assistantsCount;
            User::factory($toCreate)->assistant()->create();
            $this->command->info("✅ Creados {$toCreate} usuarios asistentes");
        } else {
            $this->command->info("ℹ️  Ya existen {$assistantsCount} usuarios asistentes");
        }

        // Crear algunos usuarios admin adicionales
        $adminsCount = User::where('role', 'ADMIN')->count();
        if ($adminsCount < 2) {
            $toCreate = 2 - $adminsCount;
            User::factory($toCreate)->admin()->create([
                'password' => Hash::make('admin123'),
            ]);
            $this->command->info("✅ Creados {$toCreate} usuarios admin adicionales");
        }
    }
    private function setAdminUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@yes-ok.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'role' => 'ADMIN',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}

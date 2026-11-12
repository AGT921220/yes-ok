<?php

namespace App\Console\Commands;

use App\Actions\CreateClientWithUserAction;
use App\Helpers\PhoneNormalizer;
use App\Models\Client;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando para crear usuarios asociados a clientes existentes que no tienen user_id.
 * 
 * Este comando detecta clientes sin user_id y crea automáticamente un usuario
 * con role=PACIENTE asociado.
 * 
 * @package App\Console\Commands
 */
class BackfillClientUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:backfill-users 
                            {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}
                            {--force : Forzar creación incluso si hay colisiones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear usuarios para clientes existentes que no tienen user_id asociado';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Buscando clientes sin usuario asociado...');
        $this->newLine();

        // Buscar clientes sin user_id
        $patientsWithoutUser = Client::whereNull('user_id')->get();

        if ($patientsWithoutUser->isEmpty()) {
            $this->info('✅ Todos los clientes ya tienen usuario asociado.');
            return Command::SUCCESS;
        }

        $this->info("📋 Encontrados {$patientsWithoutUser->count()} clientes sin usuario.");
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se realizarán cambios.');
            $this->newLine();
        }

        $action = new CreateClientWithUserAction();
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($patientsWithoutUser as $client) {
            try {
                // Verificar si ya existe un usuario con este teléfono o email
                $existingUser = null;
                
                // Intentar normalizar teléfono
                $normalizedPhone = null;
                try {
                    $cleaned = preg_replace('/[^0-9]/', '', $client->whatsapp);
                    if (strlen($cleaned) === 10 && ctype_digit($cleaned)) {
                        $normalizedPhone = PhoneNormalizer::normalizeMexicanPhone($cleaned);
                    } else {
                        $normalizedPhone = $client->whatsapp;
                    }
                } catch (\InvalidArgumentException $e) {
                    $normalizedPhone = $client->whatsapp;
                }

                // Buscar usuario existente por teléfono
                $existingUser = User::where('phone', $normalizedPhone)->first();
                
                // Si no existe por teléfono y el cliente tiene email, buscar por email
                if (!$existingUser && $client->email) {
                    $existingUser = User::where('email', $client->email)->first();
                }

                if ($existingUser && !$force) {
                    $this->warn("⚠️  Cliente #{$client->id} ({$client->full_name}): Ya existe usuario con este teléfono/email. Omitiendo.");
                    $skipped++;
                    continue;
                }

                if ($existingUser && $force) {
                    // Asociar cliente existente con usuario existente
                    if ($dryRun) {
                        $this->info("🔗 [DRY-RUN] Asociaría cliente #{$client->id} con usuario #{$existingUser->id}");
                    } else {
                        $client->user_id = $existingUser->id;
                        $client->save();
                        $this->info("✅ Cliente #{$client->id} asociado con usuario existente #{$existingUser->id}");
                    }
                    $created++;
                    continue;
                }

                // Crear nuevo usuario para el cliente
                if ($dryRun) {
                    $this->info("➕ [DRY-RUN] Crearía usuario para cliente #{$client->id} ({$client->full_name})");
                } else {
                    // Crear usuario directamente (el cliente ya existe)
                    $fullName = trim(
                        $client->first_name . ' ' . 
                        $client->paternal_surname . 
                        ($client->maternal_surname ? ' ' . $client->maternal_surname : '')
                    );

                    // Generar password
                    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
                    $password = '';
                    for ($i = 0; $i < 8; $i++) {
                        $password .= $chars[random_int(0, strlen($chars) - 1)];
                    }

                    $user = User::create([
                        'name' => $fullName,
                        'email' => $client->email,
                        'phone' => $normalizedPhone,
                        'password' => \Illuminate\Support\Facades\Hash::make($password),
                        'role' => \App\Enums\UserRole::CLIENTE,
                        'is_active' => true,
                    ]);

                    // Asociar cliente con usuario
                    $client->user_id = $user->id;
                    $client->save();

                    $this->info("✅ Usuario creado para cliente #{$client->id} ({$client->full_name})");
                    $this->line("   Usuario ID: {$user->id}");
                    $this->line("   Contraseña: {$password}");
                }
                $created++;

            } catch (\Exception $e) {
                $this->error("❌ Error con cliente #{$client->id} ({$client->full_name}): {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info('📊 Resumen:');
        $this->line("   ✅ Creados/Asociados: {$created}");
        $this->line("   ⚠️  Omitidos: {$skipped}");
        $this->line("   ❌ Errores: {$errors}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('💡 Ejecuta sin --dry-run para aplicar los cambios.');
        }

        return Command::SUCCESS;
    }
}

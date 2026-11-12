<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Crea o actualiza el administrador con ADMIN_EMAIL y ADMIN_PASSWORD del .env';

    public function handle(): int
    {
        $email = config('app.admin.email');
        $password = config('app.admin.password');
        $name = config('app.admin.name', 'Administrador');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->error('Define ADMIN_EMAIL y ADMIN_PASSWORD en el .env.');

            return Command::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->fill([
                'name' => $name,
                'password' => $password,
                'role' => UserRole::ADMIN,
                'is_active' => true,
            ]);
            $user->save();

            $this->info("Usuario administrador actualizado: {$email}");

            return Command::SUCCESS;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Usuario administrador creado: {$email}");

        return Command::SUCCESS;
    }
}

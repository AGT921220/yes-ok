<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

/**
 * Envía una notificación push de prueba a un usuario por ID.
 * Usa los tokens registrados en device_tokens (Expo o FCM).
 *
 * Uso: php artisan send:test-push {userId}
 *      php artisan send:test-push 1 --title="Hola" --body="Mensaje de prueba"
 */
class SendTestPushCommand extends Command
{
    protected $signature = 'send:test-push
                            {userId : ID del usuario (cliente o staff)}
                            {--title= : Título de la notificación}
                            {--body= : Cuerpo del mensaje}
                            {--data=* : Par clave:valor para data (ej. type=TEST appointment_id=1)}';

    protected $description = 'Envía una notificación push de prueba al usuario indicado (tokens en BD)';

    public function handle(PushNotificationService $push): int
    {
        $userId = (int) $this->argument('userId');
        $user = User::find($userId);
        if (! $user) {
            $this->error("Usuario con ID {$userId} no encontrado.");
            return Command::FAILURE;
        }

        $tokens = DeviceToken::where('user_id', $userId)->active()->get(['id', 'token', 'platform']);
        if ($tokens->isEmpty()) {
            $this->warn("El usuario no tiene tokens de push activos. Registra uno desde la app.");
            return Command::FAILURE;
        }

        $title = $this->option('title') ?: 'Prueba yes-ok';
        $body = $this->option('body') ?: 'Notificación de prueba enviada a las ' . now()->format('H:i');
        $data = [];
        foreach ($this->option('data') ?: [] as $pair) {
            if (str_contains($pair, ':')) {
                [$k, $v] = explode(':', $pair, 2);
                $data[trim($k)] = trim($v);
            }
        }
        if (empty($data)) {
            $data = ['type' => 'TEST', 'sent_at' => now()->toIso8601String()];
        }

        $this->info("Enviando push a usuario #{$userId} ({$user->email}) — {$tokens->count()} dispositivo(s).");
        $this->line("   Título: {$title}");
        $this->line("   Body: {$body}");

        $result = $push->sendToUser($userId, $title, $body, $data);

        $this->newLine();
        if ($result['success'] > 0) {
            $this->info("✅ Enviado a {$result['success']} dispositivo(s).");
        }
        if (! empty($result['invalid_tokens'])) {
            $this->warn('⚠️  ' . count($result['invalid_tokens']) . ' token(s) marcados como inválidos (revoked_at).');
        }
        if ($result['success'] === 0 && empty($result['invalid_tokens'])) {
            $this->warn('No se pudo enviar (revisa credenciales FCM/Expo o conectividad).');
        }

        return Command::SUCCESS;
    }
}

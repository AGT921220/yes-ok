<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private const EXPO_TOKEN_PREFIX = 'ExponentPushToken[';

    private ?string $projectId = null;

    private ?string $credentialsPath = null;

    private string $expoPushUrl;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');
        $this->credentialsPath = config('services.firebase.credentials');
        $this->expoPushUrl = config('services.expo_push.url', 'https://exp.host/--/api/v2/push/send');
    }

    public static function isExpoToken(string $token): bool
    {
        return str_starts_with($token, self::EXPO_TOKEN_PREFIX);
    }

    /**
     * Obtener access token OAuth2 para FCM (HTTP v1)
     */
    private function getAccessToken(): ?string
    {
        if (! $this->credentialsPath || ! $this->projectId || ! is_file($this->credentialsPath)) {
            Log::warning('PushNotificationService: Firebase credentials not configured or file missing');
            return null;
        }

        try {
            $client = new GoogleClient();
            $client->setAuthConfig($this->credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->fetchAccessTokenWithAssertion();
            $token = $client->getAccessToken();
            return $token['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('PushNotificationService: Failed to get FCM access token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Enviar push a un usuario (todos sus dispositivos activos)
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $tokens = DeviceToken::where('user_id', $userId)->active()->pluck('token')->toArray();
        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Enviar push a una lista de tokens. Soporta Expo Push Token y FCM nativo.
     * Retorna ['success' => int, 'invalid_tokens' => string[]]
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if (empty($tokens)) {
            return ['success' => 0, 'invalid_tokens' => []];
        }

        $expoTokens = array_filter($tokens, self::isExpoToken(...));
        $fcmTokens = array_diff($tokens, $expoTokens);

        $invalidTokens = [];
        $success = 0;

        if (! empty($expoTokens)) {
            [$expoSuccess, $expoInvalid] = $this->sendToTokensViaExpo($expoTokens, $title, $body, $data);
            $success += $expoSuccess;
            $invalidTokens = array_merge($invalidTokens, $expoInvalid);
        }

        if (! empty($fcmTokens)) {
            [$fcmSuccess, $fcmInvalid] = $this->sendToTokensViaFcm($fcmTokens, $title, $body, $data);
            $success += $fcmSuccess;
            $invalidTokens = array_merge($invalidTokens, $fcmInvalid);
        }

        if (! empty($invalidTokens)) {
            DeviceToken::whereIn('token', $invalidTokens)->update(['revoked_at' => now()]);
        }

        return ['success' => $success, 'invalid_tokens' => $invalidTokens];
    }

    /**
     * Enviar a tokens Expo (ExponentPushToken[...]) vía Expo Push API.
     *
     * @return array{0: int, 1: string[]} [success count, invalid tokens]
     */
    public function sendToTokensViaExpo(array $tokens, string $title, string $body, array $data = []): array
    {
        $tokens = array_values(array_filter($tokens, self::isExpoToken(...)));
        if (empty($tokens)) {
            return [0, []];
        }

        $messages = array_map(fn (string $to) => [
            'to' => $to,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'channelId' => config('services.firebase.android_channel_id', 'default'),
        ], $tokens);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip, deflate',
            'Content-Type' => 'application/json',
        ])->post($this->expoPushUrl, $messages);

        $invalidTokens = [];
        $success = 0;

        if (! $response->successful()) {
            Log::warning('PushNotificationService: Expo API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [0, $tokens];
        }

        $json = $response->json();
        $ticketData = $json['data'] ?? [];
        if (! is_array($ticketData)) {
            $ticketData = [$ticketData];
        }
        foreach ($ticketData as $i => $ticket) {
            $token = $tokens[$i] ?? null;
            if (! $token) {
                continue;
            }
            if (($ticket['status'] ?? '') === 'ok') {
                $success++;
            } else {
                $invalidTokens[] = $token;
                $err = $ticket['details']['error'] ?? $ticket['message'] ?? 'unknown';
                Log::info('PushNotificationService: Expo ticket error', [
                    'token_prefix' => substr($token, 0, 30) . '...',
                    'error' => $err,
                ]);
            }
        }

        if (! empty($json['errors'] ?? [])) {
            Log::warning('PushNotificationService: Expo API errors', ['errors' => $json['errors']]);
        }

        return [$success, $invalidTokens];
    }

    /**
     * Enviar a tokens FCM nativos.
     *
     * @return array{0: int, 1: string[]} [success count, invalid tokens]
     */
    private function sendToTokensViaFcm(array $tokens, string $title, string $body, array $data = []): array
    {
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            Log::warning('PushNotificationService: Cannot send FCM, no access token');
            return [0, $tokens];
        }

        $invalidTokens = [];
        $success = 0;
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => array_map(fn ($v) => (string) $v, $data),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => config('services.firebase.android_channel_id', 'default'),
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                $success++;
                if (config('app.debug')) {
                    Log::info('PushNotificationService: FCM sent', ['token_prefix' => substr($token, 0, 20) . '...']);
                }
            } else {
                $invalidTokens[] = $token;
                $err = $response->json('error.message') ?? $response->body();
                Log::warning('PushNotificationService: FCM failed', [
                    'token_prefix' => substr($token, 0, 20) . '...',
                    'status' => $response->status(),
                    'error' => $err,
                ]);
            }
        }

        return [$success, $invalidTokens];
    }

    /**
     * Enviar recordatorio de cita al cliente (user_id del cliente)
     */
    public function sendAppointmentReminder(User $user, int $appointmentId, string $dateTimeLabel): array
    {
        return $this->sendToUser($user->id, 'Recordatorio de cita', $dateTimeLabel, [
            'type' => 'APPOINTMENT_REMINDER',
            'appointment_id' => (string) $appointmentId,
        ]);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PushNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:push-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = env('FIREBASE_PROJECT_ID');
        $clientEmail = env('FIREBASE_CLIENT_EMAIL');
        $privateKey = str_replace('\n', "\n", (string) env('FIREBASE_PRIVATE_KEY'));
        $fcmToken = env('FCM_TEST_TOKEN');

        if (! $projectId || ! $clientEmail || $privateKey === '' || ! $fcmToken) {
            $this->error('Define FIREBASE_PROJECT_ID, FIREBASE_CLIENT_EMAIL, FIREBASE_PRIVATE_KEY y FCM_TEST_TOKEN en el .env.');

            return self::FAILURE;
        }

        // ===== GENERAR JWT =====
        $now = time();
        $expiry = $now + 3600;

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $claims = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $expiry,
        ];

        $base64Url = fn($data) =>
        rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        $jwtHeader = $base64Url($header);
        $jwtClaims = $base64Url($claims);
        $signatureInput = $jwtHeader . '.' . $jwtClaims;

        openssl_sign($signatureInput, $signature, $privateKey, 'sha256');

        $jwtSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $jwt = $signatureInput . '.' . $jwtSignature;

        // ===== PEDIR ACCESS TOKEN =====
        $tokenResponse = Http::asForm()->post(
            'https://oauth2.googleapis.com/token',
            [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]
        );

        $accessToken = $tokenResponse->json('access_token');

        // ===== ENVIAR PUSH =====
        $response = Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => 'Recordatorio de cita 🗓️',
                        'body'  => '¡Hola! Solo queríamos recordarte que tienes una cita programada para mañana a las 3:00 PM. ¡No olvides asistir! 😊',
                    ],
                    'data' => [
                        'type' => '',
                        'deep_link' => 'yes-ok://cita/123',
                    ],
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'sound' => 'sound1', // SIN .mp3
                            'channel_id' => 'appointments_channel',
                        ],
                    ],
                ],
            ]);


        dd($response->status(), $response->body());
    }
}

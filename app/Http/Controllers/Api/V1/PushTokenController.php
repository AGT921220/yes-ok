<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PushToken\StorePushTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    /**
     * Registrar o actualizar token de push para el usuario autenticado.
     * Idempotente: si el token ya existe para este user_id, se actualiza last_seen_at y metadata.
     * Si el token existe para otro usuario, se responde 409 (evitar reasignación no autorizada).
     */
    public function store(StorePushTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->input('token');
        $platform = $request->input('platform');
        $deviceName = $request->input('device_name');
        $appVersion = $request->input('app_version');

        $existing = DeviceToken::where('token', $token)->first();
        if ($existing && (int) $existing->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Token ya está registrado para otro usuario.',
            ], 409);
        }

        $deviceToken = DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $token,
            ],
            [
                'platform' => $platform,
                'device_name' => $deviceName,
                'app_version' => $appVersion,
                'last_seen_at' => now(),
                'revoked_at' => null,
            ]
        );

        return response()->json([
            'message' => 'Token registrado',
            'id' => $deviceToken->id,
        ], 201);
    }

    /**
     * Revocar token (logout o desactivar notificaciones). Acepta token en body.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $updated = DeviceToken::where('user_id', $user->id)
            ->where('token', $request->input('token'))
            ->update(['revoked_at' => now()]);

        if ($updated) {
            return response()->json(['message' => 'Token revocado']);
        }

        return response()->json(['message' => 'Token no encontrado o ya revocado'], 404);
    }
}

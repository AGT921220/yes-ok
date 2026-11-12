<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::orderBy('key')->get();
        
        return response()->json([
            'data' => $settings->map(function ($setting) {
                $numeric = is_numeric($setting->value) ? (float) $setting->value : null;

                return [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'value_numeric' => $numeric,
                ];
            }),
        ]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        // Solo ADMIN puede actualizar
        $user = auth()->user();
        if (!$user || $user->role !== \App\Enums\UserRole::ADMIN) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Solo los administradores pueden actualizar configuraciones',
                ],
            ], 403);
        }

        $rules = match ($key) {
            'consultation_price' => [
                'value' => ['required', 'numeric', 'min:0'],
            ],
            default => [
                'value' => ['required', 'string'],
            ],
        };

        $validated = $request->validate($rules);

        $setting = Setting::set($key, $validated['value']);

        return response()->json([
            'data' => [
                'key' => $setting->key,
                'value' => $setting->value,
            ],
        ]);
    }
}

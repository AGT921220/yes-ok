<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Client;
use App\Helpers\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Login con email, teléfono o password
     * Soporta login de Admin/Asistente (email + password) y Cliente (teléfono/email + password)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        info('LOGIN');
        $request->validate([
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'password' => 'required|string',
        ]);
        info('Datos de login recibidos', $request->only('email', 'phone'));
        // Validar que se proporcione email o phone (no ambos, no ninguno)
        if (!$request->has('email') && !$request->has('phone')) {
            info('No se proporcionó email ni teléfono');
            throw ValidationException::withMessages([
                'email' => ['Debe proporcionar email o teléfono'],
            ]);
        }
        info('Validación de login pasada, buscando usuario...');
        if ($request->has('email') && $request->has('phone')) {
            info('Se proporcionó email y teléfono, lo cual no es permitido');
            throw ValidationException::withMessages([
                'email' => ['Proporcione solo email o teléfono, no ambos'],
            ]);
        }

        $user = null;
        info('Intentando encontrar usuario por email o teléfono...');
        // Login por email (Admin/Asistente o Cliente)
        if ($request->has('email')) {
            $user = User::where('email', $request->email)->first();
        }

        // Login por teléfono (Cliente)
        if ($request->has('phone')) {
            try {
                $normalizedPhone = PhoneNormalizer::normalizeMexicanPhone($request->phone);
                $user = User::where('phone', $normalizedPhone)
                           ->where('role', 'CLIENTE')
                           ->first();
            } catch (\InvalidArgumentException $e) {
                info('Error al normalizar el teléfono: ' . $e->getMessage());
                throw ValidationException::withMessages([
                    'phone' => [$e->getMessage()],
                ]);
            }
        }
        info('Usuario encontrado: ' . ($user ? $user->email : 'N/A') . ', teléfono: ' . ($user ? $user->phone : 'N/A'));
        if (!$user || !Hash::check($request->password, $user->password)) {
            info('Credenciales inválidas para email: ' . ($request->email ?? 'N/A') . ', teléfono: ' . ($request->phone ?? 'N/A'));
            throw ValidationException::withMessages([
                $request->has('email') ? 'email' : 'phone' => ['Credenciales inválidas'],
            ]);
        }

        if (!$user->is_active) {
            info('Usuario inactivo: ' . ($user->email ?? 'N/A') . ', teléfono: ' . ($user->phone ?? 'N/A'));
            throw ValidationException::withMessages([
                $request->has('email') ? 'email' : 'phone' => ['Usuario inactivo'],
            ]);
        }
    info('Generando token JWT para usuario: ' . ($user->email ?? 'N/A') . ', teléfono: ' . ($user->phone ?? 'N/A'));
        $token = JWTAuth::fromUser($user);
        info('Token JWT generado exitosamente');
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Obtener usuario actual autenticado
     * 
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();
        
        return response()->json(new UserResource($user));
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo permite acceso a usuarios con rol ADMIN o ASISTENTE.
 * Los PACIENTES reciben 403.
 */
class EnsureStaffRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'No autenticado.',
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'No autenticado.'],
            ], 401);
        }

        if ($user->role === UserRole::CLIENTE) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para acceder a este recurso.',
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Acceso denegado. Recurso solo para personal.'],
            ], 403);
        }

        return $next($request);
    }
}

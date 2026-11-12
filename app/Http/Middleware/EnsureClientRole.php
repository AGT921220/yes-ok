<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo permite acceso a usuarios con rol CLIENTE.
 * ADMIN y ASISTENTE reciben 403 (deben usar las rutas de staff).
 */
class EnsureClientRole
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

        if ($user->role !== UserRole::CLIENTE) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para acceder a este recurso.',
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Acceso denegado. Recurso solo para clientes.'],
            ], 403);
        }

        return $next($request);
    }
}

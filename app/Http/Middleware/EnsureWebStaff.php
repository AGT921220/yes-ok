<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role === UserRole::CLIENTE) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'El acceso es solo para quien lleva la agenda.',
            ]);
        }

        if ($user->role !== UserRole::ADMIN && $user->role !== UserRole::ASISTENTE) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}

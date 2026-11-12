<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWebLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        $allowedEmail = env('ADMIN_WEB_EMAIL');

        // Si se define ADMIN_WEB_EMAIL, solo ese correo puede acceder.
        if ($allowedEmail && strcasecmp($email, $allowedEmail) !== 0) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas'])
                ->withInput($request->only('email'));
        }

        // Usamos el guard 'admin' basado en la tabla users (password hasheado).
        if (! Auth::guard('admin')->attempt(['email' => $email, 'password' => $password], false)) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas'])
                ->withInput($request->only('email'));
        }

        // Regenerar sesión por seguridad.
        $request->session()->regenerate();

        // Redirigir a Horizon por defecto (o a log-viewer si se quiere).
        return redirect()->intended('/horizon');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}


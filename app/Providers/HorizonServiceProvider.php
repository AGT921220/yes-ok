<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class HorizonServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Seguridad de acceso al dashboard de Horizon:
     * - En local (APP_ENV=local) se permite acceso sin restricciones.
     * - En entornos no locales, solo se permite acceso si el admin web
     *   está autenticado con el guard 'admin'.
     */
    public function boot(): void
    {
        Horizon::auth(function ($request) {
            // En entorno local, permitir siempre (útil para desarrollo).
            if (app()->environment('local')) {
                return true;
            }

            // En producción / otros entornos, usar el guard 'admin' basado en sesión.
            return Auth::guard('admin')->check();
        });

        /**
         * Opcional:
         * Si además quieres reforzar con middleware, puedes agregar en
         * config/horizon.php:
         *
         *   'middleware' => ['web', 'auth.admin'],
         *
         * donde 'auth.admin' es el alias definido en bootstrap/app.php
         * que utiliza el guard de sesión 'admin'.
         */
    }
}


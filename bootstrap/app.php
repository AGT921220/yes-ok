<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\HorizonServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('agenda.index'));
        $middleware->trustProxies(at: '*');

        // Establecer locale a español para todas las peticiones (web y API)
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        // Roles: staff = ADMIN + ASISTENTE; client = CLIENTE
        $middleware->alias([
            'role.staff' => \App\Http\Middleware\EnsureStaffRole::class,
            'role.client' => \App\Http\Middleware\EnsureClientRole::class,
            'web.staff' => \App\Http\Middleware\EnsureWebStaff::class,
            'web.admin' => \App\Http\Middleware\EnsureWebAdmin::class,
            'web.client' => \App\Http\Middleware\EnsureWebClient::class,
            // Middleware para proteger rutas solo accesibles por el admin web.
            'auth.admin' => \App\Http\Middleware\EnsureAdminAuthenticated::class,
            // Token para API de Log Viewer (header X-Log-Viewer-Token).
            'logviewer.token' => \App\Http\Middleware\LogViewerToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejar AppError con formato estándar
        $exceptions->render(function (\App\Exceptions\AppError $e, $request) {
            if ($request->is('api/*')) {
                return response()->json($e->toArray(), $e->getHttpStatus());
            }
        });

        // Manejar ValidationException de Laravel
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                // Obtener los mensajes de error (ya traducidos y personalizados)
                $errors = $e->errors();

                // Tomar el primer mensaje de error para mostrarlo como mensaje principal
                $firstErrorMessage = 'Error de validación';
                if (!empty($errors)) {
                    $firstFieldErrors = reset($errors);
                    if (is_array($firstFieldErrors) && !empty($firstFieldErrors)) {
                        $firstErrorMessage = $firstFieldErrors[0];
                    }
                }

                return response()->json([
                    'ok' => false,
                    // Mensaje principal a nivel raíz
                    'message' => $firstErrorMessage,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => $firstErrorMessage,
                        'details' => $errors,
                    ],
                    // También incluir 'errors' para compatibilidad con frontend que busca error.response?.data?.errors
                    'errors' => $errors,
                ], 400);
            }
        });

        // Manejar AuthenticationException (401 Unauthenticated)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No autenticado. Por favor, inicia sesión nuevamente.',
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'No autenticado. Por favor, inicia sesión nuevamente.',
                    ],
                ], 401);
            }
        });

        // Manejar ModelNotFoundException
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Recurso no encontrado',
                    ],
                ], 404);
            }
        });

        // Manejar errores no controlados (solo en desarrollo mostrar detalles)
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                $message = app()->environment('production')
                    ? 'Error interno del servidor'
                    : $e->getMessage();

                \Log::error('Unhandled exception in API', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'ok' => false,
                    'error' => [
                        'code' => 'INTERNAL_ERROR',
                        'message' => $message,
                    ],
                ], 500);
            }
        });
    })->create();

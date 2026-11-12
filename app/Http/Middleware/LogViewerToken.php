<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogViewerToken
{
    /**
     * Protege las rutas API de Log Viewer (/log-viewer/api/*) con un token
     * enviado en el header X-Log-Viewer-Token. No usa sesión web.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->header('X-Log-Viewer-Token');
        $expected = (string) config('log-viewer.api_token');

        if ($expected === '' || $token !== $expected) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}

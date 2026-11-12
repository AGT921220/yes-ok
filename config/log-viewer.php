<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Path
    |--------------------------------------------------------------------------
    */

    'route_path' => 'log-viewer',

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Middleware (dashboard /log-viewer)
    |--------------------------------------------------------------------------
    | UI principal: sesión web + guard admin.
    */

    'middleware' => ['web', 'auth:admin'],

    /*
    |--------------------------------------------------------------------------
    | Log Viewer API Middleware (/log-viewer/api/*)
    |--------------------------------------------------------------------------
    | Sin sesión web. Protección por header X-Log-Viewer-Token (env LOG_VIEWER_TOKEN).
    | throttle:60,1 = 60 peticiones por minuto por IP.
    */

    'api_middleware' => ['logviewer.token', 'throttle:60,1'],

    /*
    |--------------------------------------------------------------------------
    | API Token (para inyección en el frontend y validación en middleware)
    |--------------------------------------------------------------------------
    | Valor comparado con el header X-Log-Viewer-Token. Solo serializable (string).
    */

    'api_token' => env('LOG_VIEWER_TOKEN', ''),
];


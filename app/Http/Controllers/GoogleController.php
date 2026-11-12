<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;

class GoogleController extends Controller
{
    private function client(): GoogleClient
    {
        $client = new GoogleClient();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));

        // Debe coincidir con el Redirect URI autorizado en Google Cloud
        // $client->setRedirectUri(route('google.callback'));
        // GoogleController.php (en client())
        $client->setRedirectUri(config('services.google.redirect_uri'));

        // Importante para obtener refresh_token:
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        // Scope mínimo para crear eventos:
        $client->setScopes([\Google\Service\Calendar::CALENDAR_EVENTS]);

        return $client;
    }

    // /**
    //  * Redirige a Google para autorizar.
    //  */
    public function redirect()
    {
        $authUrl = $this->client()->createAuthUrl();
        return redirect()->away($authUrl);
    }

    // public function redirect()
    // {
    //     $client = $this->client();
    //     $url = $client->createAuthUrl();

    //     // Solo para debug:
    //     parse_str(parse_url($url, PHP_URL_QUERY), $q);
    //     dd($q['redirect_uri'], $url);
    // }

    /**
     * Callback OAuth: intercambia "code" por tokens y te da refresh_token.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return response("Google devolvió error: " . $request->get('error'), 400);
        }

        $code = $request->get('code');
        if (!$code) {
            return response("Falta 'code' en el callback.", 400);
        }

        $client = $this->client();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            return response("Error al obtener token: " . ($token['error_description'] ?? $token['error']), 400);
        }

        $refreshToken = $token['refresh_token'] ?? null;

        // A veces Google NO manda refresh_token si ya autorizaste antes
        if (!$refreshToken) {
            return response(
                "No llegó refresh_token.\n\n" .
                    "Solución:\n" .
                    "1) Ve a tu cuenta Google -> Seguridad -> Acceso de terceros\n" .
                    "2) Revoca/elimina el acceso de esta app\n" .
                    "3) Vuelve a entrar a /google/redirect\n\n" .
                    "Tip: Mantén setPrompt('consent') y setAccessType('offline').",
                400
            );
        }

        // Muestra el token listo para copiar al .env
        return response(
            "✅ Listo. Copia esto a tu .env:\n\n" .
                "GOOGLE_REFRESH_TOKEN={$refreshToken}\n\n" .
                "Luego reinicia tu server/config cache si aplica.",
            200
        );
    }
}

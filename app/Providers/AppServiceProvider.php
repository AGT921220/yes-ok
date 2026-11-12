<?php

namespace App\Providers;

use App\Events\AppointmentCancelled;
use App\Listeners\SendCancellationNotifications;
use App\Support\Https;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // WhatsApp Provider - Binding del puerto a la implementación según configuración
        $this->app->singleton(
            \App\Features\WhatsApp\Domain\Ports\WhatsAppSender::class,
            function ($app) {
                $provider = config('messaging.default_provider', 'twilio');

                return match ($provider) {
                    'twilio' => $this->createTwilioProvider(),
                    'meta' => $this->createMetaProvider(),
                    default => throw new \InvalidArgumentException(
                        "Provider de mensajería '{$provider}' no es válido. Use 'twilio' o 'meta'."
                    ),
                };
            }
        );
    }

    /**
     * Crea el provider de Twilio
     */
    private function createTwilioProvider(): \App\Features\WhatsApp\Infrastructure\Twilio\TwilioWhatsAppSender
    {
        $config = \App\Features\WhatsApp\Infrastructure\Twilio\TwilioWhatsAppConfig::fromEnv();
        $client = new \App\Features\WhatsApp\Infrastructure\Twilio\TwilioWhatsAppClient($config);
        return new \App\Features\WhatsApp\Infrastructure\Twilio\TwilioWhatsAppSender($client);
    }

    /**
     * Crea el provider de Meta (WhatsApp Cloud API)
     */
    private function createMetaProvider(): \App\Features\WhatsApp\Infrastructure\Meta\MetaWhatsAppCloudSender
    {
        $config = \App\Features\WhatsApp\Infrastructure\Meta\MetaWhatsAppCloudConfig::fromEnv();
        $client = new \App\Features\WhatsApp\Infrastructure\Meta\MetaWhatsAppCloudClient($config);
        return new \App\Features\WhatsApp\Infrastructure\Meta\MetaWhatsAppCloudSender($client);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Https::required()) {
            URL::forceScheme('https');
        }

        // Establecer locale a español por defecto
        app()->setLocale('es');


        Event::listen(AppointmentCancelled::class, SendCancellationNotifications::class);
    }
}

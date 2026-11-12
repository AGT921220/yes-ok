<?php

namespace App\Console\Commands;

use App\Features\WhatsApp\Application\DTO\SendTextMessageInput;
use App\Features\WhatsApp\Infrastructure\Meta\MetaWhatsAppCloudException;
use App\Features\WhatsApp\Infrastructure\Twilio\TwilioWhatsAppException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Comando de prueba para enviar mensajes WhatsApp
 * 
 * Uso:
 *   php artisan test:whatsapp
 *   php artisan test:whatsapp --to=5216144950659
 *   php artisan test:whatsapp --to=5216144950659 --message="Hola desde Laravel"
 */
class TestWhatsAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:whatsapp 
                            {--to= : Número de teléfono destino (default: env WHATSAPP_CLOUD_DEFAULT_TEST_TO)}
                            {--message= : Mensaje a enviar (default: mensaje de prueba con timestamp)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un mensaje de prueba por WhatsApp (usa el provider configurado en MESSAGING_PROVIDER)';

    public function __construct(
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {






        // ===== VARIABLES =====
        $to       = '+5218112345678';
        $doctor   = 'Dr. Juan Pérez';
        $date     = '22 Febrero 2026';
        $time     = '18:00';
        $location = 'Av. Real San Agustín 123';
        $phone    = '8110000000';

        // ===== CREDENCIALES =====
        $accountSid = (string) config('services.twilio.account_sid');
        $authToken  = (string) config('services.twilio.auth_token');
        $from       = (string) config('services.twilio.whatsapp_from');
        $contentSid = (string) config('services.twilio.whatsapp_content_sid');

        // ===== REQUEST =====
        $response = Http::asForm()
            ->withBasicAuth($accountSid, $authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from,
                'To'   => 'whatsapp:' . $to,
                'ContentSid' => $contentSid,
                'ContentVariables' => json_encode([
                    "1" => $doctor,
                    "2" => $date,
                    "3" => $time,
                    "4" => $location,
                    "5" => $phone,
                ], JSON_UNESCAPED_UNICODE),
            ]);

        // dump($response->status());
        // dd($response->body());
        // return [
        //     'status' => $response->status(),
        //     'body'   => $response->json() ?? $response->body(),
        // ];






        $provider = config('messaging.default_provider', 'twilio');
        $to = $this->option('to') ?: env('WHATSAPP_CLOUD_DEFAULT_TEST_TO', '526144950659');
        $message = $this->option('message') ?: sprintf(
            'Mensaje de prueba desde Laravel (%s) - %s',
            strtoupper($provider),
            now()->format('Y-m-d H:i:s')
        );

        $this->info("📱 Enviando mensaje WhatsApp...");
        $this->line("   Provider: {$provider}");
        $this->line("   Destino: {$to}");
        $this->line("   Mensaje: {$message}");
        $this->newLine();

        try {
            $input = SendTextMessageInput::create($to, $message);
            $result = $this->sendTextMessageUseCase->execute($input);

            $this->info("✅ Mensaje enviado exitosamente!");
            $this->line("   Message ID: {$result->messageId}");
            $this->line("   Destino: {$result->to}");

            if (!empty($result->metadata)) {
                $this->line("   Metadata: " . json_encode($result->metadata, JSON_PRETTY_PRINT));
            }

            return Command::SUCCESS;
        } catch (MetaWhatsAppCloudException $e) {
            $this->error("❌ Error al enviar mensaje (Meta WhatsApp Cloud API):");
            $this->line("   {$e->getMessage()}");

            if ($e->getStatusCode()) {
                $this->line("   Status Code: {$e->getStatusCode()}");
            }

            if ($e->getRequestId()) {
                $this->line("   Request ID: {$e->getRequestId()}");
            }

            if ($e->getResponseBody()) {
                $this->line("   Respuesta: " . json_encode($e->getResponseBody(), JSON_PRETTY_PRINT));
            }

            $this->newLine();
            $this->warn("💡 Sugerencias:");
            $this->line("   - Verifica que WHATSAPP_CLOUD_ACCESS_TOKEN sea válido");
            $this->line("   - Verifica que WHATSAPP_CLOUD_PHONE_NUMBER_ID sea correcto");
            $this->line("   - Asegúrate de que el número destino esté en formato correcto (solo dígitos)");
            $this->line("   - Si el error menciona 'template', tu cuenta puede requerir usar plantillas aprobadas");

            return Command::FAILURE;
        } catch (TwilioWhatsAppException $e) {
            $this->error("❌ Error al enviar mensaje (Twilio WhatsApp API):");
            $this->line("   {$e->getMessage()}");

            if ($e->getStatusCode()) {
                $this->line("   Status Code: {$e->getStatusCode()}");
            }

            if ($e->getMessageSid()) {
                $this->line("   Message SID: {$e->getMessageSid()}");
            }

            if ($e->getResponseBody()) {
                $this->line("   Respuesta: " . json_encode($e->getResponseBody(), JSON_PRETTY_PRINT));
            }

            $this->newLine();
            $this->warn("💡 Sugerencias:");
            $this->line("   - Verifica que TWILIO_ACCOUNT_SID sea válido");
            $this->line("   - Verifica que TWILIO_AUTH_TOKEN sea correcto");
            $this->line("   - Verifica que TWILIO_WHATSAPP_FROM esté configurado correctamente");
            $this->line("   - Asegúrate de que el número destino esté en formato E.164 (ej: +5216144950659)");
            $this->line("   - Verifica que tu cuenta de Twilio tenga WhatsApp habilitado");

            return Command::FAILURE;
        } catch (\InvalidArgumentException $e) {
            $this->error("❌ Error de validación:");
            $this->line("   {$e->getMessage()}");
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("❌ Error inesperado:");
            $this->line("   {$e->getMessage()}");
            Log::error('Error inesperado en test:whatsapp', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}

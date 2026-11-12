<?php

namespace App\Console\Commands;

use App\Features\Appointments\Jobs\GenerateUpcomingAppointmentRemindersJob;
use App\Features\Shared\Infrastructure\GoogleCalendar\CreateGoogleCalendarAppointment;
use App\Features\WhatsApp\Application\DTO\SendTextMessageInput;
use App\Features\WhatsApp\Application\UseCases\SendWhatsappMessage;
use App\Features\WhatsApp\Application\UseCases\SendWhatsappTemplate;
use App\Features\WhatsApp\Domain\Templates\AppointmentConfirmationRequestTemplate;
use App\Features\WhatsApp\Domain\ValueObjects\WhatsAppPhone;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(SendWhatsappTemplate $sendWhatsappTemplate)
    {


        $now = Carbon::now();
        info('Obteniendo citas pendientes para actualizar...');
        info('Fecha actual: ' . $now->toDateString() . ' ' . $now->toTimeString());
        $appointmentIds = Appointment::select('id')->where('attendance_status', 'pendiente')
            ->where('date', '<=', $now->toDateString())
            // ->where('end_time', '<=', $now->toTimeString())
            ->get();
        dd($appointmentIds->toArray());
    dd(1);
        GenerateUpcomingAppointmentRemindersJob::dispatch()->onQueue('notifications');
        //$this->sendWhatsappViaTwilio($sendWhatsappTemplate);
        // $appointment = Appointment::with('client')->first();
        // $client = $appointment->client;
        // // dd($appointment->toArray());

        // $date = Carbon::parse($appointment->date)->toDateString();

        // $startIso = Carbon::parse($date . ' ' . $appointment->start_time, 'America/Mexico_City')
        //     ->toIso8601String();

        // $endIso = Carbon::parse($date . ' ' . $appointment->end_time, 'America/Mexico_City')
        //     ->toIso8601String();
        // info('start: ' . $startIso);
        // info('end: ' . $endIso);
        // dd(2);
        // $createGoogleCalendarAppointment->__invoke(
        //     $client->full_name."(".$appointment->status->value.")",
        //     $startIso,
        //     $endIso,
        //     'Cita para el cliente ' . $client->full_name
        // );
    }


    private function sendWhatsappViaCloudAPI(): void
    {
        // ====== 1) Variables ======
        $to = '526144950659'; // DESTINO (sin +). OJO: aquí ya va 52 + número
        // Si prefieres mantener el otro formato: '5216144950659' también sirve.

        $doctor = 'Dr. Juan Pérez';
        $fecha = '22/02/2026';
        $hora = '18:30';
        $ubicacion = 'Consultorio 302, Torre Médica';
        $contacto = '52 81 3566 7802';

        $appointmentId = 'APT-12345';

        // ====== 2) Config (ponlo en .env) ======
        $token = (string) config('services.whatsapp.token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id'); // string
        $version = 'v22.0'; // 🔥 igual que tu curl

        $templateName = 'yes-ok_guile_appointment_confirmation_request_hxf58fd3a38e244b656ed79173ce74c06a';

        // 🔥 Tu template dice Spanish (MEX), pero prueba primero con 'es'
        $language = 'es'; // si falla, prueba 'es_MX'

        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        // ====== 3) Payload ======
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $doctor],     // {{1}}
                            ['type' => 'text', 'text' => $fecha],      // {{2}}
                            ['type' => 'text', 'text' => $hora],       // {{3}}
                            ['type' => 'text', 'text' => $ubicacion],  // {{4}}
                            ['type' => 'text', 'text' => $contacto],   // {{5}}
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'quick_reply',
                        'index' => '0',
                        'parameters' => [
                            ['type' => 'payload', 'payload' => 'CONFIRM_APPOINTMENT:' . $appointmentId],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'quick_reply',
                        'index' => '1',
                        'parameters' => [
                            ['type' => 'payload', 'payload' => 'RESCHEDULE_APPOINTMENT:' . $appointmentId],
                        ],
                    ],
                ],
            ],
        ];

        // ====== 4) Envío ======
        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

        // ====== 5) Resultado ======
        if (! $response->successful()) {
            Log::error('WhatsApp send error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $to,
                'template' => $templateName,
                'lang' => $language,
                'phone_number_id' => $phoneNumberId,
                'version' => $version,
            ]);

            throw new \RuntimeException('Error enviando WhatsApp: ' . $response->body());
        }

        Log::info('WhatsApp enviado correctamente', $response->json());
    }


    private function sendWhatsappViaTwilio(SendWhatsappTemplate $sendWhatsappTemplate): void
    {

        $template = new AppointmentConfirmationRequestTemplate(
            12345,
            "Guilebaldo",
            "Alvaro Rangel",
            "24 de febrero de 2026",
            "4:00 PM",
            "https://maps.app.goo.gl/tjD1vgtRgCGfoSeL7",
            "81 1700 0138",
        );
        $toPhone = "6144950659";
        // $toPhone = "8117000138";
        $sendWhatsappTemplate->__invoke($toPhone,$template);


        // ====== Números ======
        $from = 'whatsapp:+5218135667802';   // tu WhatsApp Sender en Twilio (si está aprobado)
        //$from = 'whatsapp:+15558680476'; // número sandbox de Twilio para pruebas (si tu número no está aprobado aún)


        $to = "whatsapp:+5218117000138"; // número sandbox de Twilio para pruebas (si tu número no está aprobado aún)
        $to = 'whatsapp:+5216142175361'; // número sandbox de Twilio para pruebas (si tu número no está aprobado aún)
        $to   = 'whatsapp:+5216144950659';   // destino    


        // ====== Variables ======
        $doctor = 'Alvaro Guilebaldo';
        $fecha = '23 de febrero de 2026';
        $hora = '4:00 PM';
        $ubicacion = 'https://maps.app.goo.gl/tjD1vgtRgCGfoSeL7';
        $contacto = '81 1700 0138';

        // ====== Credenciales ======
        $accountSid = (string) config('services.twilio.account_sid');
        $authToken  = (string) config('services.twilio.auth_token');

        // ✅ Twilio Content Template SID (HX...)
        $contentSid = (string) config('services.twilio.whatsapp_content_sid');

        // ✅ {{1}}..{{5}} como object JSON con keys string
        $contentVariables = [
            '1' => (string) $doctor,
            '2' => (string) $fecha,
            '3' => (string) $hora,
            '4' => (string) $ubicacion,
            '5' => (string) $contacto,
            '6' => (string) 'CONFIRM_APPOINTMENT:APT-12345', // payload botón 1
            '7' => (string) 'RESCHEDULE_APPOINTMENT:APT-12345', // payload botón 2
        ];

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";

        dd('Enviando mensaje WhatsApp vía Twilio...', [
            'to' => $to,
            'from' => $from,
            'contentSid' => $contentSid,
            'contentVariables' => $contentVariables,
            'url' => $url,
        ]);
        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post($url, [
                'From' => $from,
                'To' => $to,
                'ContentSid' => $contentSid,
                'ContentVariables' => json_encode($contentVariables, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT),
            ]);

        if (! $response->successful()) {
            Log::error('Twilio WhatsApp API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'from' => $from,
                'to' => $to,
                'contentSid' => $contentSid,
                'contentVariables' => $contentVariables,
            ]);

            throw new \RuntimeException('Error Twilio: ' . $response->body());
        }

        Log::info('Twilio WhatsApp sent', $response->json());
    }
}

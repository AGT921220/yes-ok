<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para webhooks de Twilio
 * Maneja mensajes entrantes y actualizaciones de estado
 */
class TwilioWebhookController extends Controller
{
    /**
     * Recibe mensajes entrantes de WhatsApp vía Twilio
     * POST /webhooks/twilio/whatsapp
     */
    public function handleInboundMessage(Request $request): JsonResponse
    {
        try {

            $action = $request->input('ButtonPayload', null);
            if ($action) {
                $payload = json_decode($action, true);

                $type = $payload['type'] ?? null;
                $appointmentId = $payload['appointment_id'] ?? null;

                if (!$type || !$appointmentId) {
                    return response()->json([
                        'message' => 'Invalid payload',
                    ], 200);
                }

                if ($type == Appointment::CONFIRMATION_WHATSAPP_TEXT_ACTION) {
                    $appointment = Appointment::find($appointmentId);
                    if ($appointment) {
                        $appointment->status = AppointmentStatus::CONFIRMADA;
                        $appointment->save();
                    }
                }
                if ($type == Appointment::CANCELLATION_WHATSAPP_TEXT_ACTION) {
                    $appointment = Appointment::find($appointmentId);
                    if ($appointment) {
                        $appointment->status = AppointmentStatus::CANCELADA;
                        $appointment->save();
                    }
                }
            }
            // Validar firma de Twilio (opcional pero recomendado)
            // $this->validateTwilioSignature($request);

            // $from = $request->input('From', '');
            // $to = $request->input('To', '');
            // $body = $request->input('Body', '');
            // $messageSid = $request->input('MessageSid', '');
            // $accountSid = $request->input('AccountSid', '');
            // $numMedia = (int) $request->input('NumMedia', 0);

            // Log::info('Mensaje entrante de Twilio WhatsApp', [
            //     'message_sid' => $messageSid,
            //     'from' => $from,
            //     'to' => $to,
            //     'body' => $body,
            //     'num_media' => $numMedia,
            // ]);

            // Normalizar número (remover prefijo whatsapp:)
            // $normalizedFrom = $this->normalizePhoneNumber($from);
            // $normalizedTo = $this->normalizePhoneNumber($to);

            // TODO: Aquí puedes procesar el mensaje según tu lógica de negocio
            // Ejemplo: crear evento, guardar en BD, enviar a cola, etc.
            // Por ahora solo logueamos

            // Respuesta a Twilio (TwiML opcional, o simplemente 200 OK)
            return response()->json([
                'status' => 'received',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al procesar mensaje entrante de Twilio', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al procesar mensaje',
            ], 500);
        }
    }

    /**
     * Recibe actualizaciones de estado de mensajes
     * POST /webhooks/twilio/status
     */
    public function handleStatusCallback(Request $request): JsonResponse
    {
        try {
            $messageSid = $request->input('MessageSid', '');
            $status = $request->input('MessageStatus', '');
            $to = $request->input('To', '');
            $from = $request->input('From', '');
            $errorCode = $request->input('ErrorCode', null);
            $errorMessage = $request->input('ErrorMessage', null);

            Log::info('Status callback de Twilio WhatsApp', [
                'message_sid' => $messageSid,
                'status' => $status,
                'to' => $to,
                'from' => $from,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            // Mapear estados de Twilio a estados internos
            $internalStatus = $this->mapTwilioStatus($status);

            // TODO: Actualizar estado del mensaje en BD si aplica
            // Ejemplo: NotificationLog::where('external_id', $messageSid)->update(['status' => $internalStatus]);

            return response()->json([
                'status' => 'processed',
                'message_sid' => $messageSid,
                'internal_status' => $internalStatus,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al procesar status callback de Twilio', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al procesar status',
            ], 500);
        }
    }

    /**
     * Normaliza número de teléfono (remueve prefijo whatsapp:)
     */
    private function normalizePhoneNumber(string $phone): string
    {
        return str_replace('whatsapp:', '', $phone);
    }

    /**
     * Mapea estados de Twilio a estados internos
     */
    private function mapTwilioStatus(string $twilioStatus): string
    {
        return match (strtolower($twilioStatus)) {
            'queued' => 'queued',
            'sent' => 'sent',
            'delivered' => 'delivered',
            'read' => 'read',
            'failed' => 'failed',
            'undelivered' => 'failed',
            default => 'unknown',
        };
    }

    /**
     * Valida la firma de Twilio (opcional, requiere configurar auth token)
     * 
     * @see https://www.twilio.com/docs/usage/webhooks/webhooks-security
     */
    private function validateTwilioSignature(Request $request): void
    {
        $authToken = config('messaging.twilio.auth_token');

        if (empty($authToken)) {
            Log::warning('No se puede validar firma de Twilio: auth_token no configurado');
            return;
        }

        $signature = $request->header('X-Twilio-Signature');
        $url = $request->fullUrl();
        $params = $request->all();

        // Ordenar parámetros
        ksort($params);
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        $computedSignature = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        if (!hash_equals($signature, $computedSignature)) {
            throw new \RuntimeException('Firma de Twilio inválida');
        }
    }
}

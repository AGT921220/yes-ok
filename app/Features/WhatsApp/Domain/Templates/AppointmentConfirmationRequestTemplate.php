<?php

namespace App\Features\WhatsApp\Domain\Templates;

use App\Features\WhatsApp\Domain\Interfaces\WhatsappTemplate;
use App\Models\Appointment;

class AppointmentConfirmationRequestTemplate implements WhatsappTemplate
{
    public function __construct(
        private int $appointmentId,
        private string $doctor,
        private string $patientName,
        private string $appointmentDate,
        private string $appointmentTime,
        private string $location,
        private string $contact,
    ) {}

    public static function getName(): string
    {
        return 'appointment_confirmation_request';
    }

    public static function getTwilioName(): string
    {
        return 'appointment_confirmation_request';
    }

    public static function getDisplayName(): string
    {
        return 'Whatsapp de Recordatorio     de cita';
    }

    public function getVariables(): array
    {
        return [
            '1'=> $this->patientName,
            '2' => $this->appointmentDate,
            '3' => $this->appointmentTime,
            '4' => $this->doctor,
            '5' => $this->location,
            '6' => $this->contact,
            '7' => json_encode([
                'type' => Appointment::CONFIRMATION_WHATSAPP_TEXT_ACTION,
                'appointment_id' => $this->appointmentId,
            ]), // payload botón 1
            '8' => json_encode([
                'type' => Appointment::CANCELLATION_WHATSAPP_TEXT_ACTION,
                'appointment_id' => $this->appointmentId,
            ]), 
        ];
    }
    // $contentVariables = [
    //     '1' => (string) $doctor,
    //     '2' => (string) $fecha,
    //     '3' => (string) $hora,
    //     '4' => (string) $ubicacion,
    //     '5' => (string) $contacto,
    //     '6' => (string) 'CONFIRM_APPOINTMENT:APT-12345', // payload botón 1
    //     '7' => (string) 'RESCHEDULE_APPOINTMENT:APT-12345', // payload botón 2
    // ];
    public function getTemplateSid(): string
    {
        return (string) config('services.twilio.whatsapp_content_sid');
    }
}

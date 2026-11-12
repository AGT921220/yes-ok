<?php

namespace App\Http\Requests\Appointment;

use App\Enums\UserRole;
use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     * Solo el ADMIN puede crear citas forzadas (cualquier día/hora).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('forced') && $this->boolean('forced') && $this->user()?->role !== UserRole::ADMIN) {
            $this->merge(['forced' => false]);
        }

        // Normalizar start_time y end_time a formato H:i si vienen con segundos
        if ($this->has('start_time') && $this->start_time) {
            $time = $this->start_time;
            // Si tiene formato H:i:s, extraer solo H:i
            if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $time, $matches)) {
                $this->merge(['start_time' => $matches[1]]);
            }
        }

        if ($this->has('end_time') && $this->end_time) {
            $time = $this->end_time;
            // Si tiene formato H:i:s, extraer solo H:i
            if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $time, $matches)) {
                $this->merge(['end_time' => $matches[1]]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'staff_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required_without:service_ids', 'nullable', 'date_format:H:i', 'after:start_time'],
            'service_ids' => ['sometimes', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'notes' => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
            'forced' => ['sometimes', 'boolean'],
            'type' => ['nullable', 'string', Rule::in(['presencial', 'virtual'])],
            'google_calendar_id' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'El cliente es requerido.',
            'client_id.integer' => 'El ID del cliente debe ser un número.',
            'client_id.exists' => 'El cliente seleccionado no existe.',
            'date.required' => 'La fecha es requerida.',
            'date.date' => 'La fecha debe tener un formato válido.',
            'date.after_or_equal' => 'La fecha debe ser hoy o una fecha futura.',
            'start_time.required' => 'La hora de inicio es requerida.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:mm (ejemplo: 09:30).',
            'end_time.required_without' => 'La hora de fin es requerida si no seleccionas servicios.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:mm (ejemplo: 10:30).',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'service_ids.array' => 'Los servicios deben enviarse como un arreglo.',
            'status.enum' => 'El estado de la cita no es válido.',
            'notes.string' => 'Las notas deben ser texto.',
            'reason.string' => 'El motivo debe ser texto.',
            'reason.max' => 'El motivo no puede exceder 500 caracteres.',
            'type.in' => 'El tipo de cita debe ser presencial o virtual.',
            'google_calendar_id.max' => 'El ID de calendario no puede exceder 500 caracteres.',
        ];
    }
}

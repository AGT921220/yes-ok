<?php

namespace App\Http\Requests\AvailabilityBlock;

use Illuminate\Foundation\Http\FormRequest;

class StoreAvailabilityBlockRequest extends FormRequest
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
     */
    protected function prepareForValidation(): void
    {
        // Normalizar start_time y end_time a formato H:i si vienen con segundos
        if ($this->has('start_time') && $this->start_time) {
            $time = $this->start_time;
            if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $time, $matches)) {
                $this->merge(['start_time' => $matches[1]]);
            }
        }

        if ($this->has('end_time') && $this->end_time) {
            $time = $this->end_time;
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time', 'after:start_time'],
            'is_full_day' => ['boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
            'is_recurring' => ['boolean'],
            'recurrence_pattern' => ['nullable', 'string', 'max:50'],
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
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio debe tener un formato válido.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.date' => 'La fecha de fin debe tener un formato válido.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:mm (ejemplo: 09:30).',
            'start_time.required_with' => 'La hora de inicio es obligatoria cuando se especifica la hora de fin.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:mm (ejemplo: 10:30).',
            'end_time.required_with' => 'La hora de fin es obligatoria cuando se especifica la hora de inicio.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'is_full_day.boolean' => 'El campo día completo debe ser verdadero o falso.',
            'reason.string' => 'El motivo debe ser texto.',
            'reason.max' => 'El motivo no puede exceder 500 caracteres.',
            'is_recurring.boolean' => 'El campo recurrente debe ser verdadero o falso.',
            'recurrence_pattern.string' => 'El patrón de recurrencia debe ser texto.',
            'recurrence_pattern.max' => 'El patrón de recurrencia no puede exceder 50 caracteres.',
        ];
    }
}

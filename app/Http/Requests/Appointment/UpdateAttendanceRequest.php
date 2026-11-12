<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['pendiente', 'asistio', 'no_asistio'])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El estado de asistencia es requerido.',
            'status.in' => 'El estado debe ser: pendiente, asistio o no_asistio.',
        ];
    }
}

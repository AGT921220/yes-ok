<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_type_id' => ['required', 'integer', 'exists:payment_types,id'],
            'method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'paid_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'El monto es requerido.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor o igual a 0.',
            'payment_type_id.required' => 'El tipo de pago es requerido.',
            'payment_type_id.exists' => 'El tipo de pago seleccionado no es válido.',
        ];
    }
}

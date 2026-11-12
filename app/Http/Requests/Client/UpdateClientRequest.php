<?php

namespace App\Http\Requests\Client;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // El middleware auth:api maneja la autorización
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $client = $this->route('client');
        $patientId = $client instanceof Client ? $client->id : $client;
        $userId = $client instanceof Client ? $client->user_id : null;

        $whatsappRules = [
            'required',
            'string',
            'regex:/^\d{10}$/',
            Rule::unique('clients', 'whatsapp')->ignore($patientId),
        ];
        $whatsappRules[] = $userId
            ? Rule::unique('users', 'phone')->ignore($userId)
            : Rule::unique('users', 'phone');

        $emailRules = [
            'nullable',
            'email',
            Rule::unique('clients', 'email')->ignore($patientId),
        ];
        $emailRules[] = $userId
            ? Rule::unique('users', 'email')->ignore($userId)
            : Rule::unique('users', 'email');

        return [
            'first_name' => ['sometimes', 'string', 'min:1'],
            'paternal_surname' => ['sometimes', 'string', 'min:1'],
            'maternal_surname' => ['nullable', 'string'],
            'whatsapp' => $whatsappRules,
            'email' => $emailRules,
            'consent_notifications' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar espacios del WhatsApp si está presente
        if ($this->has('whatsapp') && $this->whatsapp !== null) {
            $digits = preg_replace('/\D/', '', $this->whatsapp);
            $this->merge([
                'whatsapp' => strlen($digits) > 10 ? substr($digits, -10) : $digits,
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.string' => 'El nombre debe ser texto.',
            'first_name.min' => 'El nombre debe tener al menos 1 carácter.',
            'paternal_surname.string' => 'El apellido paterno debe ser texto.',
            'paternal_surname.min' => 'El apellido paterno debe tener al menos 1 carácter.',
            'maternal_surname.string' => 'El apellido materno debe ser texto.',
            'whatsapp.string' => 'El WhatsApp debe ser texto.',
            'whatsapp.regex' => 'El número debe tener exactamente 10 dígitos (México).',
            'whatsapp.unique' => 'Ya existe un cliente con este WhatsApp.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'consent_notifications.boolean' => 'El consentimiento de notificaciones debe ser verdadero o falso.',
        ];
    }
}

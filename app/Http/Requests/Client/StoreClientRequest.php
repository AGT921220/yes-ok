<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
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
        return [
            'first_name' => ['required', 'string', 'min:1'],
            'paternal_surname' => ['required', 'string', 'min:1'],
            'maternal_surname' => ['nullable', 'string'],
            'whatsapp' => [
                'required',
                'string',
                'regex:/^\d{10}$/',
                Rule::unique('clients', 'whatsapp'),
                Rule::unique('users', 'phone'),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('clients', 'email'),
                Rule::unique('users', 'email'),
            ],
            'consent_notifications' => ['boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     * Solo 10 dígitos (México): quitar todo lo que no sea dígito y tomar los últimos 10.
     */
    protected function prepareForValidation(): void
    {
        $whatsapp = $this->input('whatsapp');
        if ($whatsapp !== null && $whatsapp !== '') {
            $digits = preg_replace('/\D/', '', $whatsapp);
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
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.string' => 'El nombre debe ser texto.',
            'first_name.min' => 'El nombre debe tener al menos 1 carácter.',
            'paternal_surname.required' => 'El apellido paterno es obligatorio.',
            'paternal_surname.string' => 'El apellido paterno debe ser texto.',
            'paternal_surname.min' => 'El apellido paterno debe tener al menos 1 carácter.',
            'maternal_surname.string' => 'El apellido materno debe ser texto.',
            'whatsapp.required' => 'El teléfono es obligatorio.',
            'whatsapp.string' => 'El teléfono debe ser texto.',
            'whatsapp.regex' => 'El número debe tener exactamente 10 dígitos (México).',
            'whatsapp.unique' => 'Ya existe un cliente o usuario con este teléfono.',
            'email.email' => 'El correo es opcional, pero debe tener un formato válido.',
            'email.unique' => 'Ya existe un cliente o usuario con este correo.',
            'consent_notifications.boolean' => 'El consentimiento de notificaciones debe ser verdadero o falso.',
        ];
    }
}

<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo ADMIN puede crear usuarios
        return auth()->check() && auth()->user()->role === UserRole::ADMIN;
    }

    public function rules(): array
    {
        // REGLA DE NEGOCIO: No se pueden crear clientes desde este endpoint
        // Los clientes se crean únicamente desde el módulo de Clientes
        if ($this->role === UserRole::CLIENTE->value) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['role' => ['Los clientes no se pueden crear desde el módulo de Usuarios. Use el módulo de Clientes.']]
            );
        }

        $rules = [
            'name' => 'required|string|max:255',
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => 'required|string|min:6',
            'is_active' => 'sometimes|boolean',
            'service_ids' => ['sometimes', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ];

        // Para ASISTENTE y ADMIN, email es requerido y phone es opcional
        $rules['email'] = 'required|email|unique:users,email';
        $rules['phone'] = 'nullable|string|max:20|unique:users,phone';

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.unique' => 'Este correo electrónico ya está en uso.',
            'phone.required' => 'El teléfono es obligatorio para usuarios cliente.',
            'phone.string' => 'El teléfono debe ser texto.',
            'phone.max' => 'El teléfono no puede exceder 20 caracteres.',
            'phone.unique' => 'Este teléfono ya está en uso.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'role.required' => 'El rol es obligatorio.',
            'role.enum' => 'El rol seleccionado no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}

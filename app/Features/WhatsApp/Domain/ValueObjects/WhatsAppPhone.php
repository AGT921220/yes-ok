<?php

namespace App\Features\WhatsApp\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object para número de teléfono WhatsApp
 * Normaliza y valida números en formato E.164
 */
final class WhatsAppPhone
{
    private string $phoneNumber;

    /**
     * @param string $phoneNumber Número en cualquier formato (se normaliza)
     * @throws InvalidArgumentException Si el número no es válido
     */
    public function __construct(string $phoneNumber)
    {
        $this->phoneNumber = $this->normalize($phoneNumber);
        $this->validate();
    }

    /**
     * Normaliza el número a formato E.164 (solo dígitos)
     * Acepta números con o sin prefijo +, con espacios, guiones, etc.
     */
    private function normalize(string $phone): string
    {
        // Remover todo excepto dígitos
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Si empieza con +, ya fue removido, solo quedan dígitos
        return $cleaned;
    }

    /**
     * Valida que el número tenga longitud razonable (10-15 dígitos)
     */
    private function validate(): void
    {
        $length = strlen($this->phoneNumber);
        
        if ($length < 10 || $length > 15) {
            throw new InvalidArgumentException(
                "Número de teléfono inválido: debe tener entre 10 y 15 dígitos. Recibido: {$length} dígitos"
            );
        }

        if (!ctype_digit($this->phoneNumber)) {
            throw new InvalidArgumentException(
                "Número de teléfono inválido: solo puede contener dígitos"
            );
        }
    }

    /**
     * Retorna el número normalizado (solo dígitos, sin +)
     * WhatsApp Cloud API generalmente acepta sin el prefijo +
     */
    public function getValue(): string
    {
        return $this->phoneNumber;
    }

    /**
     * Retorna el número con prefijo + (formato E.164 completo)
     */
    public function getE164(): string
    {
        return '+' . $this->phoneNumber;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}

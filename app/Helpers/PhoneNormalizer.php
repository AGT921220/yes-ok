<?php

namespace App\Helpers;

class PhoneNormalizer
{
    /**
     * Normaliza un teléfono mexicano a formato E.164
     * 
     * @param string $phone Teléfono en formato de 10 dígitos (ej: 8123456789)
     * @return string Teléfono en formato E.164 (ej: +5218123456789)
     * @throws \InvalidArgumentException Si el teléfono no es válido
     */
    public static function normalizeMexicanPhone(string $phone): string
    {
        // Remover espacios, guiones, paréntesis y otros caracteres
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Validar que tenga exactamente 10 dígitos
        if (strlen($cleaned) !== 10) {
            throw new \InvalidArgumentException('El teléfono debe tener exactamente 10 dígitos');
        }
        
        // Validar que sean solo números
        if (!ctype_digit($cleaned)) {
            throw new \InvalidArgumentException('El teléfono solo puede contener números');
        }
        
        // Normalizar a formato E.164 México: +521{10_dígitos}
        return '+521' . $cleaned;
    }

    /**
     * Valida si un teléfono tiene formato válido (10 dígitos)
     * 
     * @param string $phone
     * @return bool
     */
    public static function isValidMexicanPhone(string $phone): bool
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return strlen($cleaned) === 10 && ctype_digit($cleaned);
    }

    /**
     * Formatea un teléfono E.164 para mostrar (opcional)
     * 
     * @param string $e164Phone Teléfono en formato E.164
     * @return string Teléfono formateado para mostrar
     */
    public static function formatForDisplay(string $e164Phone): string
    {
        // Si empieza con +521, extraer los 10 dígitos
        if (preg_match('/^\+521(\d{10})$/', $e164Phone, $matches)) {
            return $matches[1];
        }
        
        return $e164Phone;
    }
}

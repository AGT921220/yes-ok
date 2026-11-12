<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case ASISTENTE = 'ASISTENTE';
    case CLIENTE = 'CLIENTE';
}

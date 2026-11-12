<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PENDIENTE = 'PENDIENTE';
    case CONFIRMADA = 'CONFIRMADA';
    case CANCELADA = 'CANCELADA';
    case NO_SHOW = 'NO_SHOW';
    case COMPLETADA = 'COMPLETADA';
    case REQUIERE_REAGENDAR = 'REQUIERE_REAGENDAR';
}

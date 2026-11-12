<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Configuración de ventana de agendamiento (lectura; valores desde config / .env).
 */
class BookingConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        info(config('booking'));
        return response()->json([
            'data' => [
                'schedule_start' => trim((string) config('booking.schedule_start', '12:00')),
                'schedule_end' => trim((string) config('booking.schedule_end', '20:00')),
                'slot_step_minutes' => (int) config('booking.slot_step_minutes', 15),
            ],
        ]);
    }
}

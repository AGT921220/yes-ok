<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ventana de agendamiento (horario "normal", sin forzar cita)
    |--------------------------------------------------------------------------
    |
    | Formato HH:mm (24 h). Se valida en AppointmentService y en slots públicos.
    | Modifica en .env: BOOKING_SCHEDULE_START y BOOKING_SCHEDULE_END
    |
    */

    'schedule_start' => env('BOOKING_SCHEDULE_START', '12:00'),

    'schedule_end' => env('BOOKING_SCHEDULE_END', '20:00'),

    /*
    | Paso entre slots (minutos) para slots públicos y referencia en API.
    | El panel de agenda usa el mismo valor vía /public/booking-config
    |
    */

    'slot_step_minutes' => (int) env('BOOKING_SLOT_STEP_MINUTES', 15),

];

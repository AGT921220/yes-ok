<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Messaging Provider
    |--------------------------------------------------------------------------
    |
    | Selecciona el provider de mensajería por defecto.
    | Opciones: 'twilio', 'meta' (WhatsApp Cloud API)
    |
    */
    'default_provider' => env('MESSAGING_PROVIDER', 'twilio'),

    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration
    |--------------------------------------------------------------------------
    */
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
        'status_callback_url' => env('TWILIO_STATUS_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API Configuration
    |--------------------------------------------------------------------------
    */
    'meta' => [
        'access_token' => env('WHATSAPP_CLOUD_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_CLOUD_PHONE_NUMBER_ID'),
        'api_version' => env('WHATSAPP_CLOUD_API_VERSION', 'v20.0'),
        'from_phone_number' => env('WHATSAPP_CLOUD_FROM_PHONE_NUMBER'),
    ],
];

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// routes/api.php

Route::post('/twilio/fallback', function (Request $request) {
    \Log::info('Fallback de Twilio recibido', $request->all());
    return response('ok', 200);
});

Route::post('/twilio/webhook',[\App\Http\Controllers\Api\V1\TwilioWebhookController::class, 'handleInboundMessage']);

Route::prefix('v1')->group(function () {
    Route::get('/test', function () {
        return 'Test';
    });
    // Health check
    Route::get('/', function () {
        return response()->json([
            'message' => 'yes-ok API v1.0.0',
            'status' => 'running'
        ]);
    });

    // Autenticación (público)
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);

    // Solo autenticación (cualquier rol): /auth/me, push-tokens
    Route::middleware('auth:api')->group(function () {
        Route::get('/auth/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);
        Route::post('/push-tokens', [\App\Http\Controllers\Api\V1\PushTokenController::class, 'store']);
        Route::delete('/push-tokens', [\App\Http\Controllers\Api\V1\PushTokenController::class, 'destroy']);
    });

    // Rutas solo para staff (ADMIN + ASISTENTE). Clientes reciben 403.
    Route::middleware(['auth:api', 'role.staff'])->group(function () {
        Route::apiResource('clients', \App\Http\Controllers\Api\V1\ClientController::class)
            ->names('api.clients');
        Route::post('/clients/{client}/set-password', [\App\Http\Controllers\Api\V1\ClientController::class, 'setPassword']);

        Route::get('/appointments/reschedule-queue', [\App\Http\Controllers\Api\V1\AppointmentController::class, 'rescheduleQueue']);
        Route::get('/appointments/availability-slots', [\App\Http\Controllers\Api\V1\AppointmentController::class, 'availabilitySlots']);
        Route::patch('/appointments/{appointment}/attendance', [\App\Http\Controllers\Api\V1\AppointmentController::class, 'updateAttendance']);
        Route::get('/appointments/{appointment}/payments', [\App\Http\Controllers\Api\V1\AppointmentController::class, 'payments']);
        Route::post('/appointments/{appointment}/payments', [\App\Http\Controllers\Api\V1\AppointmentController::class, 'storePayment']);
        Route::delete('/appointments/{appointment}/payments/{payment}', [\App\Http\Controllers\Api\V1\AppointmentController::class, 'destroyPayment']);
        Route::post('/appointments/{appointment}/cancel', [\App\Http\Controllers\Api\V1\AppointmentController::class, 'cancel']);
        Route::apiResource('appointments', \App\Http\Controllers\Api\V1\AppointmentController::class);

        Route::apiResource('availability-blocks', \App\Http\Controllers\Api\V1\AvailabilityBlockController::class)->only(['index', 'store', 'destroy']);

        Route::apiResource('services', \App\Http\Controllers\Api\V1\ServiceController::class)
            ->names('api.services');

        Route::apiResource('users', \App\Http\Controllers\Api\V1\UserController::class);

        Route::get('/payment-types', [\App\Http\Controllers\Api\V1\PaymentTypeController::class, 'index']);

        Route::get('/settings', [\App\Http\Controllers\Api\V1\SettingController::class, 'index']);
        Route::put('/settings/{key}', [\App\Http\Controllers\Api\V1\SettingController::class, 'update']);

        Route::get('/reports/appointments-stats', [\App\Http\Controllers\Api\V1\ReportController::class, 'appointmentStats']);
        Route::get('/reports/export/excel', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportExcel']);
        Route::get('/reports/export/pdf', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportPDF']);

        Route::get('/notification-logs', [\App\Http\Controllers\Api\V1\NotificationLogController::class, 'index']);

        Route::post('/seed', [\App\Http\Controllers\Api\V1\SeedController::class, 'seed']);
    });

    // Rutas solo para CLIENTE (portal con login JWT). Solo sus datos.
    Route::middleware(['auth:api', 'role.client'])->prefix('me')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\V1\MeController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\Api\V1\MeController::class, 'updateProfile']);
        Route::get('/appointments', [\App\Http\Controllers\Api\V1\MeController::class, 'appointments']);
        Route::get('/appointments/{appointment}', [\App\Http\Controllers\Api\V1\MeController::class, 'showAppointment']);
    });
    
    // Rutas públicas (sin autenticación)
    Route::prefix('public')->group(function () {
        Route::get('/booking-config', \App\Http\Controllers\Api\V1\BookingConfigController::class);
        Route::get('/booking-staff', [\App\Http\Controllers\Api\V1\PublicController::class, 'bookingStaff']);
        Route::get('/available-slots', [\App\Http\Controllers\Api\V1\PublicController::class, 'availableSlots']);
        Route::post('/book', [\App\Http\Controllers\Api\V1\PublicController::class, 'bookAppointment'])->middleware('throttle:10,1'); // 10 intentos por minuto
        Route::post('/client-access', [\App\Http\Controllers\Api\V1\PublicController::class, 'requestClientAccess']);
        Route::get('/client-portal', [\App\Http\Controllers\Api\V1\PublicController::class, 'getClientPortal']);
        Route::post('/client-portal/cancel/{appointment}', [\App\Http\Controllers\Api\V1\PublicController::class, 'cancelAppointment']);
        Route::post('/client-portal/reschedule/{appointment}', [\App\Http\Controllers\Api\V1\PublicController::class, 'rescheduleAppointment']);

        // Catálogo público de servicios (solo lectura)
        Route::get('/services', [\App\Http\Controllers\Api\V1\ServiceController::class, 'index']);
    });

    // Webhooks de Twilio (sin autenticación, pero con validación de firma opcional)
    Route::prefix('webhooks/twilio')->group(function () {
        Route::post('/whatsapp', [\App\Http\Controllers\Api\V1\TwilioWebhookController::class, 'handleInboundMessage']);
        Route::post('/status', [\App\Http\Controllers\Api\V1\TwilioWebhookController::class, 'handleStatusCallback']);
    });
});

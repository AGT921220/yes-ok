<?php

use App\Enums\UserRole;
use App\Http\Controllers\AdminWebLoginController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\Web\AgendaController;
use App\Http\Controllers\Web\BlockPageController;
use App\Http\Controllers\Web\ClientPageController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\ReportPageController;
use App\Http\Controllers\Web\ReschedulePageController;
use App\Http\Controllers\Web\ServicePageController;
use App\Http\Controllers\Web\SettingPageController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

Route::post('/twilio/webhook', function (Request $request) {
    \Log::info('Webhook de Twilio recibido WEB', $request->all());

    return response('Webhook recibido', 200);
});

Route::post('/twilio/fallback', function (Request $request) {
    \Log::info('Fallback de Twilio recibido', $request->all());

    return response('Fallback recibido', 200);
});

Route::middleware('web')->group(function () {
    Route::get('/admin/login', [AdminWebLoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AdminWebLoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('admin.login.post');
    Route::post('/admin/logout', [AdminWebLoginController::class, 'logout'])->name('admin.logout');
});

Route::get('/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

Route::get('/', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    if ($user->role === UserRole::CLIENTE) {
        auth()->logout();

        return redirect()->route('login');
    }

    return redirect()->route('agenda.index');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/agendar', fn () => redirect()->route('login'))->name('booking.create');
Route::post('/agendar', fn () => redirect()->route('login'))->name('booking.store');

Route::middleware(['auth', 'web.staff'])->group(function () {
    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::post('/agenda', [AgendaController::class, 'store'])->name('agenda.store');
    Route::get('/agenda/{appointment}', [AgendaController::class, 'show'])->name('agenda.show');
    Route::post('/agenda/{appointment}/cancelar', [AgendaController::class, 'cancel'])->name('agenda.cancel');
    Route::post('/agenda/{appointment}/confirmar', [AgendaController::class, 'confirm'])->name('agenda.confirm');
    Route::post('/agenda/{appointment}/asistencia', [AgendaController::class, 'attendance'])->name('agenda.attendance');

    Route::resource('clientes', ClientPageController::class)
        ->parameters(['clientes' => 'client'])
        ->except(['show'])
        ->names('clients');

    Route::get('/bloqueos', [BlockPageController::class, 'index'])->name('blocks.index');
    Route::post('/bloqueos', [BlockPageController::class, 'store'])->name('blocks.store');
    Route::delete('/bloqueos/{block}', [BlockPageController::class, 'destroy'])->name('blocks.destroy');

    Route::get('/reagendar', [ReschedulePageController::class, 'index'])->name('reschedule.index');
    Route::get('/reportes', [ReportPageController::class, 'index'])->name('reports.index');

    Route::middleware('web.admin')->group(function () {
        Route::resource('servicios', ServicePageController::class)
            ->parameters(['servicios' => 'service'])
            ->except(['show'])
            ->names('services');

        Route::get('/configuracion', [SettingPageController::class, 'index'])->name('settings.index');
        Route::put('/configuracion/{key}', [SettingPageController::class, 'update'])
            ->where('key', '.*')
            ->name('settings.update');
    });
});

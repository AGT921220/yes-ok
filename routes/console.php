<?php

use App\Features\Appointments\Jobs\GenerateUpcomingAppointmentRemindersJob;
use App\Features\Appointments\Jobs\UpdatePendingAppointmentsAttendanceJob;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job((new GenerateUpcomingAppointmentRemindersJob)->onQueue('notifications'))
    ->dailyAt('12:00')
    // ->everyFiveMinutes()
    // ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job((new UpdatePendingAppointmentsAttendanceJob)->onQueue('appointments'))
    ->hourly()
    // ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
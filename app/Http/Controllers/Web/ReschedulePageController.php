<?php

namespace App\Http\Controllers\Web;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;

class ReschedulePageController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with(['client', 'staff', 'services'])
            ->where('status', AppointmentStatus::REQUIERE_REAGENDAR)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('reschedule.index', compact('appointments'));
    }
}

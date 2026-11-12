<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingPageController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'settings' => Setting::orderBy('key')->get(),
            'scheduleStart' => config('booking.schedule_start'),
            'scheduleEnd' => config('booking.schedule_end'),
            'slotStep' => config('booking.slot_step_minutes'),
        ]);
    }

    public function update(Request $request, string $key)
    {
        $data = $request->validate([
            'value' => ['required', 'string', 'max:1000'],
        ]);

        Setting::set($key, $data['value']);

        return back()->with('success', 'Configuración guardada.');
    }
}

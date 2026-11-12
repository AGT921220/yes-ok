<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class ReportPageController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::query();

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $appointments = $query->get();
        $total = $appointments->count();
        $byStatus = [];

        foreach ($appointments as $appointment) {
            $status = $appointment->status->value;
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
        }

        $cancelled = $byStatus['CANCELADA'] ?? 0;
        $noShow = $byStatus['NO_SHOW'] ?? 0;

        return view('reports.index', [
            'total' => $total,
            'byStatus' => $byStatus,
            'cancellationRate' => $total > 0 ? round($cancelled / $total * 100, 1) : 0,
            'noShowRate' => $total > 0 ? round($noShow / $total * 100, 1) : 0,
        ]);
    }
}

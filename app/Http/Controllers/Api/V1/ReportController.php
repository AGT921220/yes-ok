<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function appointmentStats(Request $request): JsonResponse
    {
        $query = Appointment::query();

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $appointments = $query->get();
        $total = $appointments->count();

        $byStatus = [];
        foreach ($appointments as $apt) {
            $status = $apt->status->value;
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
        }

        $cancelled = $byStatus['CANCELADA'] ?? 0;
        $noShow = $byStatus['NO_SHOW'] ?? 0;

        return response()->json([
            'data' => [
                'total' => $total,
                'by_status' => $byStatus,
                'cancellation_rate' => $total > 0 ? ($cancelled / $total * 100) : 0,
                'no_show_rate' => $total > 0 ? ($noShow / $total * 100) : 0,
                'inasistencia_rate' => $total > 0 ? ($noShow / $total * 100) : 0,
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        // TODO: Implementar exportación a Excel usando Laravel Excel (Maatwebsite)
        // Por ahora retornar error indicando que requiere el paquete
        return response()->json([
            'ok' => false,
            'error' => [
                'code' => 'NOT_IMPLEMENTED',
                'message' => 'Exportación a Excel requiere el paquete maatwebsite/excel. Instalar con: composer require maatwebsite/excel',
            ],
        ], 501);
    }

    public function exportPDF(Request $request)
    {
        // TODO: Implementar exportación a PDF usando DomPDF o similar
        // Por ahora retornar error indicando que requiere el paquete
        return response()->json([
            'ok' => false,
            'error' => [
                'code' => 'NOT_IMPLEMENTED',
                'message' => 'Exportación a PDF requiere el paquete barryvdh/laravel-dompdf. Instalar con: composer require barryvdh/laravel-dompdf',
            ],
        ], 501);
    }
}

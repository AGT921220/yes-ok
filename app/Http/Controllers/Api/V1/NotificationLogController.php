<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificationLog::with(['client', 'appointment'])
            ->orderBy('sent_at', 'desc');

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $limit = $request->get('limit', 100);
        $logs = $query->limit($limit)->get();

        return response()->json([
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'client_id' => $log->client_id,
                    'patient_name' => $log->client ? $log->client->full_name : null,
                    'appointment_id' => $log->appointment_id,
                    'channel' => $log->channel,
                    'template_name' => $log->template_name,
                    'message' => $log->message,
                    'status' => $log->status,
                    'sent_at' => $log->sent_at?->toIso8601String(),
                    'error_message' => $log->error_message,
                ];
            }),
        ]);
    }
}

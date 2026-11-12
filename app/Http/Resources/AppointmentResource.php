<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $durationMinutes = (int) Carbon::parse($this->end_time)->diffInMinutes(Carbon::parse($this->start_time));
        if ($this->relationLoaded('services') && $this->services->isNotEmpty()) {
            $durationMinutes = (int) $this->services->sum('duration_minutes');
        }

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'staff_user_id' => $this->staff_user_id,
            'date' => $this->date->format('Y-m-d'),
            'start_time' => is_string($this->start_time) ? $this->start_time : Carbon::parse($this->start_time)->format('H:i'),
            'end_time' => is_string($this->end_time) ? $this->end_time : Carbon::parse($this->end_time)->format('H:i'),
            'status' => $this->status->value,
            'notes' => $this->notes,
            'reason' => $this->reason,
            'duration_minutes' => $durationMinutes,
            'created_by' => $this->created_by,
            'booked_by_client' => $this->booked_by_client,
            'forced' => $this->forced ?? false,
            'attendance_status' => $this->attendance_status ?? 'pendiente',
            'payment_status' => $this->payment_status ?? 'sin_pagar',
            'type' => $this->type ?? 'presencial',
            'google_calendar_id' => $this->google_calendar_id,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'client' => $this->whenLoaded('client', fn() => new ClientResource($this->client)),
            'staff' => $this->whenLoaded('staff', fn() => $this->staff ? [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'role' => $this->staff->role?->value ?? (string) $this->staff->role,
            ] : null),
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'duration_minutes' => $s->duration_minutes,
                'price' => $s->price !== null ? (float) $s->price : null,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'payment_type_id' => $p->payment_type_id,
                'payment_type' => $p->relationLoaded('paymentType') && $p->paymentType ? ['id' => $p->paymentType->id, 'name' => $p->paymentType->name] : null,
                'notes' => $p->notes,
                'paid_at' => $p->paid_at?->toIso8601String(),
                'created_at' => $p->created_at?->toIso8601String(),
            ])),
        ];
    }

    /**
     * Formatear tiempo (TIME de MySQL)
     */
    private function formatTime($time): string
    {
        if (is_string($time)) {
            // Si ya es string, puede ser "10:00:00" o "10:00"
            return substr($time, 0, 5); // Tomar solo HH:mm
        }
        
        return Carbon::parse($time)->format('H:i');
    }
}

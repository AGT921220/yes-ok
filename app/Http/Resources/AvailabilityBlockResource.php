<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityBlockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'start_time' => $this->start_time ? (is_string($this->start_time) ? substr($this->start_time, 0, 5) : Carbon::parse($this->start_time)->format('H:i')) : null,
            'end_time' => $this->end_time ? (is_string($this->end_time) ? substr($this->end_time, 0, 5) : Carbon::parse($this->end_time)->format('H:i')) : null,
            'is_full_day' => $this->is_full_day,
            'reason' => $this->reason,
            'is_recurring' => $this->is_recurring,
            'recurrence_pattern' => $this->recurrence_pattern,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'date_label' => $this->formatDateRange(),

        ];
    }
    private function formatDateRange()
    {
        $start = Carbon::parse($this->start_date)
            ->timezone('America/Mexico_City')
            ->translatedFormat('j M');

        $end = Carbon::parse($this->end_date)
            ->timezone('America/Mexico_City')
            ->translatedFormat('j M');

        return $this->start_date == $this->end_date
            ? $start
            : "{$start} - {$end}";
    }
}

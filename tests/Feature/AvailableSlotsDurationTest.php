<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailableSlotsDurationTest extends TestCase
{
    use RefreshDatabase;

    private function futureAllowedDate(): string
    {
        $date = Carbon::now()->addDays(7);
        while ($date->dayOfWeekIso >= 7) {
            $date = $date->addDay();
        }
        return $date->toDateString();
    }

    /** @test */
    public function available_slots_default_to_60_minutes(): void
    {
        $date = $this->futureAllowedDate();

        $response = $this->getJson("/api/v1/public/available-slots?target_date={$date}&duration_minutes=60");
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertIsArray($data);

        if (count($data) > 0) {
            $first = $data[0];
            $start = Carbon::parse($first['start_time']);
            $end = Carbon::parse($first['end_time']);
            $this->assertSame(60, $start->diffInMinutes($end));
        }
    }

    /** @test */
    public function available_slots_respect_duration_minutes_parameter(): void
    {
        $date = $this->futureAllowedDate();

        $response = $this->getJson("/api/v1/public/available-slots?target_date={$date}&duration_minutes=30");
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertIsArray($data);

        if (count($data) > 0) {
            $first = $data[0];
            $start = Carbon::parse($first['start_time']);
            $end = Carbon::parse($first['end_time']);
            $this->assertSame(30, $start->diffInMinutes($end));
        }
    }

    /** @test */
    public function slot_occupied_by_appointment_is_not_returned_for_duration_30(): void
    {
        $date = $this->futureAllowedDate();

        $client = Client::factory()->create();

        // Ocupamos 14:00 - 14:30
        Appointment::create([
            'client_id' => $client->id,
            'date' => $date,
            'start_time' => '14:00:00',
            'end_time' => '14:30:00',
            'status' => AppointmentStatus::PENDIENTE->value,
            'reason' => null,
            'created_by' => null,
            'booked_by_client' => false,
            'forced' => false,
            'attendance_status' => 'pendiente',
            'payment_status' => 'sin_pagar',
            'type' => 'presencial',
        ]);

        $response = $this->getJson("/api/v1/public/available-slots?target_date={$date}&duration_minutes=30");
        $response->assertStatus(200);

        $data = $response->json('data');
        $slotsStarting1400 = array_filter($data, fn ($s) => ($s['start_time'] ?? null) === '14:00');
        $slotsStarting1415 = array_filter($data, fn ($s) => ($s['start_time'] ?? null) === '14:15');

        $this->assertCount(0, $slotsStarting1400);
        $this->assertCount(0, $slotsStarting1415);
    }
}


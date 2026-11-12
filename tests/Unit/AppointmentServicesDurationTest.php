<?php

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AppointmentServicesDurationTest extends TestCase
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
    public function end_time_is_calculated_from_single_service_duration(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $client = Client::factory()->create();

        $service = Service::create([
            'name' => 'Corte',
            'duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $date = $this->futureAllowedDate();

        $appointmentService = new AppointmentService();
        $appointment = $appointmentService->create([
            'client_id' => $client->id,
            'date' => $date,
            'start_time' => '14:00',
            'service_ids' => [$service->id],
            'status' => AppointmentStatus::PENDIENTE->value,
            'reason' => 'Test',
            'booked_by_client' => false,
        ], $user);

        $this->assertSame('14:00', Carbon::parse($appointment->start_time)->format('H:i'));
        $this->assertSame('14:30', Carbon::parse($appointment->end_time)->format('H:i'));
        $this->assertCount(1, $appointment->services);
        $this->assertSame(30, $appointment->services->sum('duration_minutes'));
    }

    /** @test */
    public function end_time_is_calculated_from_multiple_services(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $client = Client::factory()->create();

        $s30 = Service::create([
            'name' => 'Corte',
            'duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $s15 = Service::create([
            'name' => 'Barba',
            'duration_minutes' => 15,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $date = $this->futureAllowedDate();

        $appointmentService = new AppointmentService();
        $appointment = $appointmentService->create([
            'client_id' => $client->id,
            'date' => $date,
            'start_time' => '15:00',
            'service_ids' => [$s30->id, $s15->id],
            'status' => AppointmentStatus::PENDIENTE->value,
            'reason' => 'Test',
            'booked_by_client' => false,
        ], $user);

        $this->assertSame('15:45', Carbon::parse($appointment->end_time)->format('H:i'));
        $this->assertCount(2, $appointment->services);
        $this->assertSame(45, $appointment->services->sum('duration_minutes'));
    }

    /** @test */
    public function overlapping_appointments_with_variable_durations_are_rejected(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $patient1 = Client::factory()->create();
        $patient2 = Client::factory()->create();

        $s30 = Service::create([
            'name' => 'Corte',
            'duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $s60 = Service::create([
            'name' => 'Peinado',
            'duration_minutes' => 60,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $date = $this->futureAllowedDate();
        $appointmentService = new AppointmentService();

        $appointmentService->create([
            'client_id' => $patient1->id,
            'date' => $date,
            'start_time' => '14:00',
            'service_ids' => [$s30->id],
            'status' => AppointmentStatus::PENDIENTE->value,
            'reason' => 'A',
            'booked_by_client' => false,
        ], $user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Existe un empalme con otra cita');

        $appointmentService->create([
            'client_id' => $patient2->id,
            'date' => $date,
            'start_time' => '14:15',
            'service_ids' => [$s60->id],
            'status' => AppointmentStatus::PENDIENTE->value,
            'reason' => 'B',
            'booked_by_client' => false,
        ], $user);
    }
}


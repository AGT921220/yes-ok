<?php

namespace Tests\Unit;

use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class AppointmentScheduleValidationTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AppointmentService();
    }

    private function validateSchedule(string $date, string $start, string $end, bool $forced): void
    {
        $method = new ReflectionMethod(AppointmentService::class, 'validateAllowedSchedule');
        $method->setAccessible(true);
        $method->invoke($this->service, $date, $start, $end, $forced);
    }

    public function test_allowed_schedule_accepts_monday_12_to_20(): void
    {
        $this->validateSchedule('2026-02-09', '12:00', '12:30', false);
        $this->validateSchedule('2026-02-09', '19:30', '20:00', false);
        $this->assertTrue(true);
    }

    public function test_allowed_schedule_rejects_sunday(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Horario no permitido');
        $this->validateSchedule('2026-02-08', '14:00', '14:30', false);
    }

    public function test_allowed_schedule_rejects_before_12(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Horario no permitido');
        $this->validateSchedule('2026-02-09', '11:00', '11:30', false);
    }

    public function test_allowed_schedule_rejects_after_20(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Horario no permitido');
        $this->validateSchedule('2026-02-09', '19:30', '20:30', false);
    }

    public function test_forced_bypasses_schedule_validation(): void
    {
        $this->validateSchedule('2026-02-08', '10:00', '10:30', true);
        $this->validateSchedule('2026-02-09', '21:00', '21:30', true);
        $this->assertTrue(true);
    }
}

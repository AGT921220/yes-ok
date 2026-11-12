<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AppointmentCancellationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    private function authJson(User $user, string $method, string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        $token = $this->tokenFor($user);
        return $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json($method, $uri, $data);
    }

    /** @test */
    public function cancelled_appointment_is_not_returned_in_index_used_by_calendar(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $client = Client::factory()->create();

        $appointment = Appointment::factory()->create([
            'client_id' => $client->id,
        ]);

        $startDate = $appointment->date->toDateString();
        $endDate = $startDate;

        $response = $this->authJson($admin, 'GET', '/api/v1/appointments', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($appointment->id, $ids);

        $cancelResponse = $this->authJson($admin, 'POST', "/api/v1/appointments/{$appointment->id}/cancel", [
            'cancellation_reason' => 'Prueba de cancelación',
        ]);

        $cancelResponse->assertStatus(200);

        $responseAfter = $this->authJson($admin, 'GET', '/api/v1/appointments', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $responseAfter->assertStatus(200);
        $idsAfter = collect($responseAfter->json('data'))->pluck('id')->all();
        $this->assertNotContains($appointment->id, $idsAfter);
    }
}


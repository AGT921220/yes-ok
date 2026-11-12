<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PatientPortalAccessTest extends TestCase
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
    public function patient_receives_403_on_staff_routes(): void
    {
        $user = User::factory()->client()->create(['phone' => '+528111234567']);
        $client = Client::factory()->create(['user_id' => $user->id, 'whatsapp' => '8111234567']);

        $response = $this->authJson($user, 'GET', '/api/v1/clients');
        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');

        $response = $this->authJson($user, 'GET', '/api/v1/appointments');
        $response->assertStatus(403);

        $response = $this->authJson($user, 'GET', '/api/v1/users');
        $response->assertStatus(403);

        $response = $this->authJson($user, 'GET', '/api/v1/settings');
        $response->assertStatus(403);
    }

    /** @test */
    public function patient_can_access_me_profile_and_gets_own_data(): void
    {
        $user = User::factory()->client()->create(['phone' => '+528111234567', 'name' => 'Juan Pérez']);
        $client = Client::factory()->create([
            'user_id' => $user->id,
            'whatsapp' => '8111234567',
            'first_name' => 'Juan',
            'paternal_surname' => 'Pérez',
            'email' => 'juan@test.com',
        ]);

        $response = $this->authJson($user, 'GET', '/api/v1/me/profile');
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $client->id);
        $response->assertJsonPath('data.whatsapp', '8111234567');
        $response->assertJsonPath('data.full_name', 'Juan Pérez');
    }

    /** @test */
    public function patient_sees_only_own_appointments(): void
    {
        $user = User::factory()->client()->create(['phone' => '+528111234567']);
        $client = Client::factory()->create(['user_id' => $user->id, 'whatsapp' => '8111234567']);

        $ownAppointment = Appointment::factory()->create(['client_id' => $client->id]);

        $otherPatient = Client::factory()->create(['whatsapp' => '8119876543']);
        $otherAppointment = Appointment::factory()->create(['client_id' => $otherPatient->id]);

        $response = $this->authJson($user, 'GET', '/api/v1/me/appointments');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($ownAppointment->id, $ids);
        $this->assertNotContains($otherAppointment->id, $ids);
    }

    /** @test */
    public function patient_cannot_view_another_patients_appointment_via_me(): void
    {
        $user = User::factory()->client()->create(['phone' => '+528111234567']);
        $client = Client::factory()->create(['user_id' => $user->id, 'whatsapp' => '8111234567']);

        $otherPatient = Client::factory()->create(['whatsapp' => '8119876543']);
        $otherAppointment = Appointment::factory()->create(['client_id' => $otherPatient->id]);

        $response = $this->authJson($user, 'GET', '/api/v1/me/appointments/' . $otherAppointment->id);
        $response->assertStatus(403);
    }

    /** @test */
    public function patient_can_view_own_appointment_via_me(): void
    {
        $user = User::factory()->client()->create(['phone' => '+528111234567']);
        $client = Client::factory()->create(['user_id' => $user->id, 'whatsapp' => '8111234567']);
        $appointment = Appointment::factory()->create(['client_id' => $client->id, 'reason' => 'Consulta general']);

        $response = $this->authJson($user, 'GET', '/api/v1/me/appointments/' . $appointment->id);
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $appointment->id);
        $response->assertJsonPath('data.reason', 'Consulta general');
    }

    /** @test */
    public function admin_can_access_staff_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->authJson($admin, 'GET', '/api/v1/clients');
        $response->assertStatus(200);

        $response = $this->authJson($admin, 'GET', '/api/v1/appointments');
        $response->assertStatus(200);
    }

    /** @test */
    public function patient_receives_403_on_me_routes_when_calling_with_staff_guard(): void
    {
        // Staff (admin) must not use /me routes; they get 403
        $admin = User::factory()->admin()->create();

        $response = $this->authJson($admin, 'GET', '/api/v1/me/profile');
        $response->assertStatus(403);
    }
}

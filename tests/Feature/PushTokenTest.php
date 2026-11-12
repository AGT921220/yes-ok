<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PushTokenTest extends TestCase
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
    public function store_registers_token_and_returns_201(): void
    {
        $user = User::factory()->client()->create();
        $payload = [
            'token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'platform' => 'android',
            'device_name' => 'Pixel 6',
            'app_version' => '1.0.0',
        ];

        $response = $this->authJson($user, 'POST', '/api/v1/push-tokens', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Token registrado');
        $response->assertJsonStructure(['id']);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => $payload['token'],
            'platform' => 'android',
            'device_name' => 'Pixel 6',
            'app_version' => '1.0.0',
        ]);
    }

    /** @test */
    public function store_upserts_same_user_token_updates_metadata(): void
    {
        $user = User::factory()->client()->create();
        $token = 'ExponentPushToken[sameuser]';
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'platform' => 'ios',
            'last_seen_at' => now()->subDays(2),
        ]);

        $response = $this->authJson($user, 'POST', '/api/v1/push-tokens', [
            'token' => $token,
            'platform' => 'android',
            'device_name' => 'Updated',
            'app_version' => '2.0.0',
        ]);
        $response->assertStatus(201);

        $record = DeviceToken::where('user_id', $user->id)->where('token', $token)->first();
        $this->assertSame('android', $record->platform);
        $this->assertSame('Updated', $record->device_name);
        $this->assertSame('2.0.0', $record->app_version);
        $this->assertNotNull($record->last_seen_at);
        $this->assertNull($record->revoked_at);
    }

    /** @test */
    public function store_returns_409_when_token_belongs_to_another_user(): void
    {
        $userA = User::factory()->client()->create();
        $userB = User::factory()->client()->create();
        $token = 'ExponentPushToken[shared]';
        DeviceToken::create([
            'user_id' => $userB->id,
            'token' => $token,
            'platform' => 'android',
        ]);

        $response = $this->authJson($userA, 'POST', '/api/v1/push-tokens', [
            'token' => $token,
            'platform' => 'android',
        ]);
        $response->assertStatus(409);
        $response->assertJsonPath('message', 'Token ya está registrado para otro usuario.');

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseHas('device_tokens', ['user_id' => $userB->id]);
    }

    /** @test */
    public function store_validates_required_fields(): void
    {
        $user = User::factory()->client()->create();
        $response = $this->authJson($user, 'POST', '/api/v1/push-tokens', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token', 'platform']);
    }

    /** @test */
    public function destroy_revokes_token(): void
    {
        $user = User::factory()->client()->create();
        $token = 'ExponentPushToken[revoke-me]';
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'platform' => 'android',
        ]);

        $response = $this->authJson($user, 'DELETE', '/api/v1/push-tokens', ['token' => $token]);
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Token revocado');

        $record = DeviceToken::where('user_id', $user->id)->where('token', $token)->first();
        $this->assertNotNull($record->revoked_at);
    }

    /** @test */
    public function destroy_returns_404_when_token_not_found(): void
    {
        $user = User::factory()->client()->create();
        $response = $this->authJson($user, 'DELETE', '/api/v1/push-tokens', [
            'token' => 'ExponentPushToken[nonexistent]',
        ]);
        $response->assertStatus(404);
    }

    /** @test */
    public function unauthenticated_requests_receive_401(): void
    {
        $this->json('POST', '/api/v1/push-tokens', [
            'token' => 'ExponentPushToken[xxx]',
            'platform' => 'android',
        ])->assertStatus(401);

        $this->json('DELETE', '/api/v1/push-tokens', ['token' => 'ExponentPushToken[xxx]'])
            ->assertStatus(401);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_push_accepts_payload_without_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'device_id' => 'test-device-123',
            'last_synced_at' => now()->subDay()->toIso8601String(),
            'records' => [
                'members' => [],
                'offerings' => [],
            ],
        ];

        $response = $this->withoutMiddleware()->postJson('/api/v1/sync/push', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'synced',
                    'conflicts',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }
}

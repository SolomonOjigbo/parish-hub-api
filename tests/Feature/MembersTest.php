<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_members_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/members');

        $response->assertStatus(401);
    }

    public function test_get_members_returns_data_when_authenticated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->withoutMiddleware()->getJson('/api/v1/members');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                ],
            ]);
    }

    public function test_create_member_correctly(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->withoutMiddleware()->postJson('/api/v1/members', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'marital_status' => 'single',
            'primary_phone' => '08012345678',
            'address_line1' => '123 Test Street',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('members', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }
}

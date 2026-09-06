<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_parishioner_cannot_create_user_accounts(): void
    {
        $this->actingAsRole('parishioner');

        $this->postJson('/api/v1/users', [
            'name' => 'Escalator',
            'email' => 'escalator@example.com',
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_parishioner_cannot_grant_roles(): void
    {
        $target = User::factory()->create();
        $this->actingAsRole('parishioner');

        $this->postJson("/api/v1/users/{$target->id}/roles", [
            'role' => 'super_admin',
        ])->assertStatus(403);

        $this->assertFalse($target->fresh()->hasRole('super_admin'));
    }

    public function test_parishioner_cannot_read_finance_or_members(): void
    {
        $this->actingAsRole('parishioner');

        $this->getJson('/api/v1/offerings')->assertStatus(403);
        $this->getJson('/api/v1/tithes')->assertStatus(403);
        $this->getJson('/api/v1/members')->assertStatus(403);
    }

    public function test_parishioner_cannot_send_bulk_sms(): void
    {
        $this->actingAsRole('parishioner');

        $this->postJson('/api/v1/communications/sms', [
            'recipient_type' => 'all',
            'message' => 'spam',
        ])->assertStatus(403);
    }

    public function test_finance_officer_can_read_offerings_but_not_delete(): void
    {
        $this->actingAsRole('finance_officer');

        $this->getJson('/api/v1/offerings')->assertStatus(200);
        $this->getJson('/api/v1/members')->assertStatus(200);
        $this->postJson('/api/v1/users', [
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'password123',
        ])->assertStatus(403);
    }

    public function test_super_admin_can_read_members(): void
    {
        $this->actingAsRole('super_admin');

        $this->getJson('/api/v1/members')->assertStatus(200);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/members')->assertStatus(401);
        $this->getJson('/api/v1/offerings')->assertStatus(401);
    }

    public function test_pending_password_change_blocks_protected_routes(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/members')
            ->assertStatus(403)
            ->assertJson(['must_change_password' => true]);

        // Auth self-service routes stay reachable so the password can be changed.
        $this->getJson('/api/v1/auth/me')->assertStatus(200);
    }
}

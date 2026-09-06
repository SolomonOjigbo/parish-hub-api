<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression tests for the {id}-vs-model-name route binding bug: detail
 * routes must 404 on a missing id instead of injecting an empty model.
 */
class RouteModelBindingTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_finance_detail_routes_return_404_for_missing_records(): void
    {
        $this->actingAsSuperAdmin();

        $this->getJson('/api/v1/offerings/999999')->assertStatus(404);
        $this->getJson('/api/v1/tithes/999999')->assertStatus(404);
        $this->getJson('/api/v1/pledges/999999')->assertStatus(404);
        $this->getJson('/api/v1/donations/999999')->assertStatus(404);
    }

    public function test_finance_updates_and_deletes_return_404_for_missing_records(): void
    {
        $this->actingAsSuperAdmin();

        $this->deleteJson('/api/v1/offerings/999999')->assertStatus(404);
        $this->deleteJson('/api/v1/tithes/999999')->assertStatus(404);
        $this->deleteJson('/api/v1/donations/999999')->assertStatus(404);
    }

    public function test_bulletin_and_communication_log_detail_routes_return_404(): void
    {
        $this->actingAsSuperAdmin();

        $this->getJson('/api/v1/bulletins/999999')->assertStatus(404);
        $this->getJson('/api/v1/communications/logs/999999')->assertStatus(404);
    }

    public function test_user_and_role_routes_return_404_for_missing_records(): void
    {
        $this->actingAsSuperAdmin();

        $this->putJson('/api/v1/users/999999', ['name' => 'X'])->assertStatus(404);
        $this->deleteJson('/api/v1/users/999999')->assertStatus(404);
        $this->postJson('/api/v1/users/999999/roles', ['role' => 'secretary'])->assertStatus(404);
        $this->putJson('/api/v1/roles/999999', ['name' => 'x'])->assertStatus(404);
    }
}

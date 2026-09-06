<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers the member sub-resource endpoints that were TODO stubs.
 */
class MemberSubresourcesTest extends TestCase
{
    use RefreshDatabase;

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $this->member = Member::create([
            'first_name' => 'Ifeanyi',
            'last_name' => 'Nwachukwu',
            'gender' => 'male',
            'marital_status' => 'married',
            'status' => 'active',
        ]);
    }

    public function test_can_add_and_update_sacramental_record(): void
    {
        $create = $this->postJson("/api/v1/members/{$this->member->id}/sacraments", [
            'type' => 'baptism',
            'date' => '1990-06-10',
            'church' => 'Holy Cross Cathedral, Lagos',
            'minister' => 'Fr. John Obi',
        ]);

        $create->assertStatus(201)->assertJsonPath('success', true);

        $sacramentId = $create->json('data.id');

        $this->putJson("/api/v1/members/{$this->member->id}/sacraments/{$sacramentId}", [
            'church' => 'St. Ferdinand Catholic Church',
        ])->assertStatus(200);

        $this->assertDatabaseHas('sacramental_records', [
            'id' => $sacramentId,
            'member_id' => $this->member->id,
            'church' => 'St. Ferdinand Catholic Church',
        ]);
    }

    public function test_updating_another_members_sacrament_returns_404(): void
    {
        $other = Member::create([
            'first_name' => 'Ngozi',
            'last_name' => 'Eze',
            'gender' => 'female',
            'marital_status' => 'single',
            'status' => 'active',
        ]);
        $record = $other->sacramentalRecords()->create(['type' => 'baptism']);

        $this->putJson("/api/v1/members/{$this->member->id}/sacraments/{$record->id}", [
            'church' => 'Hijacked',
        ])->assertStatus(404);
    }

    public function test_member_societies_attendance_and_communications_endpoints_respond(): void
    {
        $this->getJson("/api/v1/members/{$this->member->id}/societies")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/members/{$this->member->id}/attendance")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/members/{$this->member->id}/communications")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_holy_orders_is_a_valid_sacrament_and_religious_a_valid_marital_status(): void
    {
        $this->postJson("/api/v1/members/{$this->member->id}/sacraments", [
            'type' => 'holy_orders',
            'date' => '2010-07-01',
        ])->assertStatus(201);

        $this->postJson('/api/v1/members', [
            'first_name' => 'Fr. Peter',
            'last_name' => 'Adigwe',
            'gender' => 'male',
            'marital_status' => 'religious',
            'primary_phone' => '08022223333',
        ])->assertStatus(201);
    }
}

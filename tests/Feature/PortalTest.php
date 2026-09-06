<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsParishioner(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $member = Member::create([
            'first_name' => 'Chinedu',
            'last_name' => 'Okafor',
            'gender' => 'male',
            'marital_status' => 'single',
            'status' => 'active',
        ]);
        $member->contactDetail()->create(['primary_phone' => '08010000000']);

        $user = User::factory()->create(['member_id' => $member->id]);
        $user->assignRole('parishioner');
        Sanctum::actingAs($user);

        return [$user, $member];
    }

    public function test_profile_update_persists_contact_details(): void
    {
        [, $member] = $this->actingAsParishioner();

        $response = $this->putJson('/api/v1/portal/profile', [
            'primary_phone' => '08099998888',
            'lga' => 'Ikeja',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('member_contact_details', [
            'member_id' => $member->id,
            'primary_phone' => '08099998888',
            'lga' => 'Ikeja',
        ]);
    }

    public function test_parishioner_can_register_for_portal_event(): void
    {
        [, $member] = $this->actingAsParishioner();

        $creator = User::factory()->create();
        $event = Event::create([
            'title' => 'Annual Retreat',
            'type' => 'retreat',
            'start_datetime' => now()->addDays(10),
            'requires_registration' => true,
            'created_by' => $creator->id,
        ]);

        $this->postJson("/api/v1/portal/events/{$event->id}/register")
            ->assertStatus(201);

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_portal_events_lists_registration_status(): void
    {
        $this->actingAsParishioner();

        $creator = User::factory()->create();
        Event::create([
            'title' => 'Harvest Planning',
            'type' => 'other',
            'start_datetime' => now()->addDays(5),
            'requires_registration' => true,
            'created_by' => $creator->id,
        ]);

        $response = $this->getJson('/api/v1/portal/events');

        $response->assertStatus(200);
        $this->assertSame('Harvest Planning', $response->json('data.0.title'));
        $this->assertFalse($response->json('data.0.is_registered'));
    }
}

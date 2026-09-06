<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_member_with_contact_details(): void
    {
        $response = $this->postJson('/api/v1/public/register', [
            'first_name' => 'Adaeze',
            'last_name' => 'Okonkwo',
            'date_of_birth' => '1995-04-12',
            'gender' => 'female',
            'primary_phone' => '08031234567',
            'email' => 'adaeze@example.ng',
            'address_line1' => '15 Church Street, Ipaja',
            'lga' => 'Alimosho',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['member_id', 'membership_number']]);

        $member = Member::find($response->json('data.member_id'));

        $this->assertNotNull($member);
        $this->assertSame('inactive', $member->status, 'Self-registrations await parish office review');
        $this->assertStringStartsWith('SFC-', $member->membership_number);

        $this->assertDatabaseHas('member_contact_details', [
            'member_id' => $member->id,
            'primary_phone' => '08031234567',
            'email' => 'adaeze@example.ng',
            'lga' => 'Alimosho',
        ]);
    }

    public function test_visitor_card_creates_visitor_record_not_member(): void
    {
        $response = $this->postJson('/api/v1/public/visitor', [
            'name' => 'Tobi Adewale',
            'phone' => '08087654321',
            'heard_from' => 'A friend',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('visitors', [
            'name' => 'Tobi Adewale',
            'phone' => '08087654321',
        ]);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_public_events_returns_upcoming_events(): void
    {
        $creator = User::factory()->create();

        Event::create([
            'title' => 'Sunday Mass',
            'type' => 'mass',
            'start_datetime' => now()->addDays(3),
            'end_datetime' => now()->addDays(3)->addHours(2),
            'location' => 'Main Church',
            'created_by' => $creator->id,
        ]);

        Event::create([
            'title' => 'Far Future Feast',
            'type' => 'feast_day',
            'start_datetime' => now()->addDays(90),
            'created_by' => $creator->id,
        ]);

        $response = $this->getJson('/api/v1/public/events');

        $response->assertStatus(200)->assertJsonPath('success', true);

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Sunday Mass'));
        $this->assertFalse($titles->contains('Far Future Feast'), 'Only the next 30 days are public');
    }
}

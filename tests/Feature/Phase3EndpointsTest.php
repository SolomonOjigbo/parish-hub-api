<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase3EndpointsTest extends TestCase
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

    private function makeMember(): Member
    {
        return Member::create([
            'first_name' => 'Ngozi',
            'last_name' => 'Okafor',
            'gender' => 'female',
            'marital_status' => 'married',
            'status' => 'active',
            'date_joined' => now()->toDateString(),
        ]);
    }

    public function test_notifications_endpoint_returns_derived_items(): void
    {
        $this->actingAsSuperAdmin();
        $this->makeMember(); // recent registration → notification

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)->assertJsonPath('success', true);
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('New member registered'));
    }

    public function test_notifications_respect_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('parishioner');
        Sanctum::actingAs($user);
        $this->makeMember();

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        $this->assertSame([], $response->json('data'), 'Parishioners see no admin notifications');
    }

    public function test_dashboard_summary_returns_permission_gated_sections(): void
    {
        $this->actingAsSuperAdmin();
        $this->makeMember();

        $response = $this->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['member_count', 'sunday_offering', 'giving_trend', 'society_activity']]);
        $this->assertSame(1, $response->json('data.member_count'));
    }

    public function test_sacrament_certificate_downloads_as_pdf(): void
    {
        $this->actingAsSuperAdmin();
        $member = $this->makeMember();
        $record = $member->sacramentalRecords()->create(['type' => 'baptism', 'date' => '1990-01-01']);

        $response = $this->get("/api/v1/members/{$member->id}/sacraments/{$record->id}/certificate");

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_donation_receipt_downloads_as_pdf(): void
    {
        $user = $this->actingAsSuperAdmin();
        $donation = Donation::create([
            'donor_name' => 'Chief Emeka',
            'amount' => 50000,
            'purpose' => 'Harvest',
            'donation_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'recorded_by' => $user->id,
        ]);

        $response = $this->get("/api/v1/donations/{$donation->id}/receipt");

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_members_import_creates_members_from_csv(): void
    {
        $this->actingAsSuperAdmin();

        $csv = "first_name,last_name,primary_phone,gender,lga\nAdaeze,Nwosu,08011112222,female,Ikeja\nEmeka,Obi,08033334444,male,Alimosho\n,,\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('members.csv', $csv);

        $response = $this->post('/api/v1/members/import', ['file' => $file], ['Accept' => 'application/json']);

        $response->assertStatus(200)
            ->assertJsonPath('data.imported', 2);

        $this->assertDatabaseHas('members', ['first_name' => 'Adaeze', 'last_name' => 'Nwosu']);
        $this->assertDatabaseHas('member_contact_details', ['primary_phone' => '08033334444', 'lga' => 'Alimosho']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Offering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfferingsSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_offerings_summary_returns_correct_shape(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Offering::factory()->create([
            'amount' => 1000.50,
            'collection_date' => now(),
        ]);

        Offering::factory()->create([
            'amount' => 2000.75,
            'collection_date' => now(),
        ]);

        $response = $this->withoutMiddleware()->getJson('/api/v1/offerings/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_amount',
                    'total_count',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }
}

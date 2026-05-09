<?php

namespace Database\Factories;

use App\Models\Offering;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offering>
 */
class OfferingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'collection_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'envelope_number' => fake()->optional()->numerify('ENV-####'),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'pos', 'cheque']),
            'transfer_reference' => fake()->optional()->numerify('REF-########'),
            'is_anonymous' => fake()->boolean(10), // 10% chance of being anonymous
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}

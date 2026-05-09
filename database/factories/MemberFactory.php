<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'membership_number' => 'SFC-' . fake()->unique()->numberBetween(1000, 9999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'marital_status' => fake()->randomElement(['single', 'married', 'widowed', 'divorced']),
            'status' => 'active',
        ];
    }
}

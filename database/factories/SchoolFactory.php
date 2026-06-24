<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'npsn' => fake()->unique()->numerify('########'),
            'name' => 'SDN ' . fake()->company() . ' Kota',
            'address' => fake()->address(),
            'postal_code' => fake()->postcode(),
            'phone_number' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'status' => 'active',
        ];
    }
}

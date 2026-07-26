<?php

namespace Database\Factories;

use App\Models\ItemUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemUnit>
 */
class ItemUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'is_active' => true,
        ];
    }
}

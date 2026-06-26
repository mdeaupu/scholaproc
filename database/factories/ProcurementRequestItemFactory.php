<?php

namespace Database\Factories;

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementRequestItem>
 */
class ProcurementRequestItemFactory extends Factory
{
    protected $model = ProcurementRequestItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'procurement_request_id' => ProcurementRequest::factory(),
            'line_number' => $this->faker->numberBetween(1, 10),
            'item_name' => $this->faker->words(3, true),
            'specification' => $this->faker->sentence(),
            'unit' => $this->faker->randomElement(['unit', 'box', 'pcs', 'set']),
            'quantity' => $this->faker->numberBetween(1, 100),
            'estimated_price' => $this->faker->numberBetween(10000, 1000000),
            'official_price' => $this->faker->optional()->numberBetween(10000, 1000000),
            'is_pph' => false,
        ];
    }
}

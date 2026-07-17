<?php

namespace Database\Factories;

use App\Models\ItemUnit;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementRequestItem>
 */
class ProcurementRequestItemFactory extends Factory
{
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
            'unit_id' => ItemUnit::first() ?? ItemUnit::factory(),
            'quantity' => $this->faker->numberBetween(1, 50),
            'estimated_price' => $this->faker->randomElement([50000, 100000, 150000]),
            'official_price' => null,
            'is_pph' => false,
            'negotiation_status' => 'not_started',
        ];
    }
}

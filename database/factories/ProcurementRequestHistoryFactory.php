<?php

namespace Database\Factories;

use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementRequestHistory>
 */
class ProcurementRequestHistoryFactory extends Factory
{
    protected $model = ProcurementRequestHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'procurement_request_id' => ProcurementRequest::factory(),
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement([
                'draft', 'submitted', 'verified', 'supplier_assigned',
                'items_prepared', 'completed', 'rejected',
            ]),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}

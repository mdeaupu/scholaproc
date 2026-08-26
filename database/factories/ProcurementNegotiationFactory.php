<?php

namespace Database\Factories;

use App\Models\ProcurementNegotiation;
use App\Models\ProcurementRequestItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementNegotiation>
 */
class ProcurementNegotiationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'procurement_request_item_id' => ProcurementRequestItem::factory(),
            'round_number' => 1,
            'offered_by' => ProcurementNegotiation::OFFERED_BY_ADMIN,
            'user_id' => User::factory(),
            'offered_price' => $this->faker->randomElement([50000, 75000, 100000, 125000, 150000]),
            'status' => ProcurementNegotiation::STATUS_PENDING,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => ProcurementNegotiation::STATUS_PENDING]);
    }

    public function countered(): static
    {
        return $this->state(fn () => ['status' => ProcurementNegotiation::STATUS_COUNTERED]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => ProcurementNegotiation::STATUS_ACCEPTED]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => ProcurementNegotiation::STATUS_REJECTED]);
    }

    public function fromSchool(): static
    {
        return $this->state(fn () => [
            'offered_by' => ProcurementNegotiation::OFFERED_BY_SCHOOL,
        ]);
    }

    public function fromAdmin(): static
    {
        return $this->state(fn () => [
            'offered_by' => ProcurementNegotiation::OFFERED_BY_ADMIN,
        ]);
    }
}

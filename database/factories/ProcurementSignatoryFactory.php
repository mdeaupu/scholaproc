<?php

namespace Database\Factories;

use App\Models\ProcurementRequest;
use App\Models\ProcurementSignatory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementSignatory>
 */
class ProcurementSignatoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProcurementSignatory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'procurement_request_id' => ProcurementRequest::factory(),
            'role' => $this->faker->randomElement(['owner', 'admin_cv', 'admin_school']),
            'name' => $this->faker->name(),
            'nip' => $this->faker->numerify('##################'),
            'title' => $this->faker->jobTitle(),
        ];
    }
}

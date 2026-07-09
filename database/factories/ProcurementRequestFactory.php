<?php

namespace Database\Factories;

use App\Models\ProcurementRequest;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProcurementRequest>
 */
class ProcurementRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'package_category_id' => 1,
            'budget_year_id' => 1,
            'funding_source_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

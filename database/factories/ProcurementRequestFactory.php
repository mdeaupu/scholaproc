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
            'uuid' => (string) Str::uuid(),
            'school_id' => School::factory(),
            'status' => fake()->randomElement(['submitted', 'verified', 'completed', 'rejected']),
            'package_category' => fake()->randomElement(['Alat Tulis Kantor', 'Sarana Prasarana', 'Elektronik Kelas']),
            'budget_year' => 2026,
            'funding_source' => 'Dana BOS Reguler',
            'start_date' => now()->addDays(2),
            'end_date' => now()->addMonths(1),
            'work_duration_text' => '30 Hari Kerja',
            'is_taxable' => true,
            'ppn_rate' => 11.00,
            'requested_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\BudgetYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetYear>
 */
class BudgetYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => (string) now()->year,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\PackageCategory;
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
            'package_category_id' => PackageCategory::first() ?? PackageCategory::factory(),
            'budget_year_id' => BudgetYear::first() ?? BudgetYear::factory(),
            'funding_source_id' => FundingSource::first() ?? FundingSource::factory(),
            'uuid' => Str::uuid()->toString(),
            'status' => 'draft',
            'is_taxable' => true,
            'ppn_rate' => 11.00,
            'pph_22_rate' => 0.00,
            'pph_23_rate' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

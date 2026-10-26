<?php

namespace Database\Factories;

use App\Models\Subsidiary;
use App\Models\SubsidiaryLegalDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubsidiaryLegalDocument>
 */
class SubsidiaryLegalDocumentFactory extends Factory
{
    protected $model = SubsidiaryLegalDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subsidiary_id' => Subsidiary::factory(),
            'document_type' => $this->faker->randomElement(['business_permit', 'deed', 'npwp', 'other']),
            'document_number' => $this->faker->unique()->numerify('DOC-#####'),
            'document_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'notary_name' => $this->faker->optional()->name,
            'issuer' => $this->faker->optional()->company,
            'valid_until' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
        ];
    }

    public function businessPermit(): self
    {
        return $this->state(fn () => ['document_type' => 'business_permit']);
    }

    public function deed(): self
    {
        return $this->state(fn () => ['document_type' => 'deed']);
    }

    public function expired(): self
    {
        return $this->state(fn () => ['valid_until' => $this->faker->dateTimeBetween('-2 years', '-1 day')]);
    }

    public function permanent(): self
    {
        return $this->state(fn () => ['valid_until' => null]);
    }
}

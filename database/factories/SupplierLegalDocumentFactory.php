<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\SupplierLegalDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierLegalDocument>
 */
class SupplierLegalDocumentFactory extends Factory
{
    protected $model = SupplierLegalDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentTypes = ['business_permit', 'deed', 'npwp', 'other'];
        $type = $this->faker->randomElement($documentTypes);

        return [
            'supplier_id' => Supplier::factory(),
            'document_type' => $type,
            'document_number' => $this->faker->unique()->numerify('DOC-#####'),
            'document_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'notary_name' => $this->faker->optional()->name,
            'issuer' => $this->faker->optional()->company,
            'valid_until' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
        ];
    }

    public function businessPermit(): self
    {
        return $this->state(fn() => [
            'document_type' => 'business_permit',
        ]);
    }

    public function deed(): self
    {
        return $this->state(fn() => [
            'document_type' => 'deed',
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn() => [
            'valid_until' => $this->faker->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }

    public function permanent(): self
    {
        return $this->state(fn() => [
            'valid_until' => null,
        ]);
    }
}

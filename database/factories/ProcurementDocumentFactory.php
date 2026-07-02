<?php

namespace Database\Factories;

use App\Models\ProcurementDocument;
use App\Models\ProcurementRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcurementDocument>
 */
class ProcurementDocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProcurementDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['cover', 'planning', 'negotiation', 'purchase_order', 'inspection', 'bast', 'invoice', 'receipt'];

        return [
            'procurement_request_id' => ProcurementRequest::factory(),
            'document_type' => $this->faker->randomElement($types),
            'document_number' => $this->faker->unique()->bothify('DOC-2026/06/####'),
            'document_date' => $this->faker->date(),
        ];
    }
}

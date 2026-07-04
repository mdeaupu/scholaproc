<?php

namespace Database\Factories;

use App\Models\GeneratedDocument;
use App\Models\ProcurementRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GeneratedDocument>
 */
class GeneratedDocumentFactory extends Factory
{
    protected $model = GeneratedDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([
            'cover',
            'planning',
            'negotiation',
            'purchase_order',
            'inspection',
            'bast',
            'invoice',
            'receipt',
        ]);

        return [
            'procurement_request_id' => ProcurementRequest::factory(),
            'document_type' => $type,
            'file_path' => 'temp/' . $type . '_' . Str::uuid() . '.pdf',
            'download_token' => Str::random(64),
        ];
    }

    public function forType(string $type): self
    {
        return $this->state(fn() => ['document_type' => $type]);
    }
}
